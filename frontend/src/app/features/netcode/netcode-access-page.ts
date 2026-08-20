import { Component, OnDestroy, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';
import {
  LucideArrowLeft,
  LucideCirclePlay,
  LucideCopy,
  LucideExternalLink,
  LucideKey,
  LucideLock,
  LucidePhone,
  LucideRefreshCcw,
  LucideUser,
  LucideVideo,
} from '@lucide/angular';

import {
  NetcodeApi,
  NetcodeSearchResponse,
  NetcodeValidationStep,
  NetflixProfileValidationResponse,
  Tutorial,
} from './netcode-api';
import { LgMonogramLogo } from '../../shared/lg-monogram-logo';
import Swal from 'sweetalert2';

type AccessStep = 'whatsapp' | 'nombre' | 'pin' | 'account';
type ViewState = 'access' | 'scan' | 'result';
type SearchResultType = 'codigo' | 'link' | 'login';

const MAX_TIME = 60;
const POLL_MS = 2000;
const MAX_SEARCH_ATTEMPTS = 2;

@Component({
  selector: 'app-netcode-access-page',
  imports: [
    FormsModule,
    LucideArrowLeft,
    LucideCirclePlay,
    LucideCopy,
    LucideExternalLink,
    LucideKey,
    LucideLock,
    LucidePhone,
    LucideRefreshCcw,
    LucideUser,
    LucideVideo,
    LgMonogramLogo,
  ],
  templateUrl: './netcode-access-page.html',
  styleUrl: './netcode-codes-page.css',
})
export class NetcodeAccessPage implements OnDestroy {
  private readonly api = inject(NetcodeApi);

  readonly viewState = signal<ViewState>('access');
  readonly step = signal<AccessStep>('whatsapp');
  readonly whatsapp = signal('');
  readonly clientAccess = signal('');
  readonly clientAccessPin = signal('');
  readonly profileOptions = signal<NonNullable<NetflixProfileValidationResponse['profiles']>>([]);
  readonly profileName = signal('');
  readonly pin = signal('');
  readonly attempts = signal({ whatsapp: 0, nombre: 0, pin: 0 });
  readonly account = signal<NetflixProfileValidationResponse | null>(null);
  readonly tutorials = signal<Record<string, Tutorial>>({});
  readonly tutorialOpen = signal<Tutorial | null>(null);
  readonly toast = signal('');
  readonly timeLeft = signal(MAX_TIME);
  readonly scanStatus = signal('Buscando correo reciente...');
  readonly resultValue = signal('');
  readonly resultType = signal<SearchResultType | ''>('');
  readonly resultEmail = signal('');
  readonly resultReceivedAt = signal('');
  readonly resultProcessedAt = signal('');
  readonly resultValiditySource = signal<'processed_at' | 'received_at'>('processed_at');
  readonly resultSecondsLeft = signal(0);
  readonly resultExpiresAt = signal('');
  readonly searchAttempt = signal(0);
  readonly searchFinishedWithoutResult = signal(false);
  readonly isSearching = signal(false);

  private countdown: number | null = null;
  private pollingTimer: number | null = null;
  private resultValidityCountdown: number | null = null;
  private pollingRequest: Subscription | null = null;
  private activeSearch: { accountId: number | null; email: string } | null = null;
  private successfulPollInCurrentSearch = false;

  readonly resultIsLink = computed(() => this.resultType() === 'link');
  readonly resultIsCode = computed(() => this.resultType() === 'codigo');
  readonly resultIsLogin = computed(() => this.resultType() === 'login');
  readonly accessCodeBotUrl = computed(() => this.account()?.cuenta?.bot_acceso4_masked_url || '');
  readonly resultValidityLabel = computed(() => this.formatSeconds(this.resultSecondsLeft()));
  readonly searchAttemptLabel = computed(() => `Busqueda ${Math.max(this.searchAttempt(), 1)} de ${MAX_SEARCH_ATTEMPTS}`);
  readonly canRetrySearch = computed(
    () =>
      this.searchFinishedWithoutResult() &&
      !this.isSearching() &&
      !this.resultValue() &&
      this.searchAttempt() > 0 &&
      this.searchAttempt() < MAX_SEARCH_ATTEMPTS
  );
  readonly resultTitle = computed(() => {
    if (this.resultIsLink()) return 'LINK ENCONTRADO';
    if (this.resultIsLogin()) return 'LOGIN ENCONTRADO';

    return 'CODIGO ENCONTRADO';
  });
  readonly resultPrimaryActionLabel = computed(() => {
    if (this.resultIsLink()) return 'ABRIR LINK';
    if (this.resultIsLogin()) return 'COPIAR LOGIN';

    return 'COPIAR CODIGO';
  });
  readonly resultSecondaryActionLabel = computed(() => (this.resultIsLink() ? 'COPIAR LINK' : 'NUEVA BUSQUEDA'));
  readonly resultReceivedLabel = computed(() => this.formatBackendDateLabel(this.resultReceivedAt()));
  readonly resultProcessedLabel = computed(() => this.formatBackendDateLabel(this.resultProcessedAt()));
  readonly resultExpiresLabel = computed(() => this.formatBackendDateLabel(this.resultExpiresAt()));

  constructor() {
    this.api.tutorials().subscribe({
      next: (tutorials) => this.tutorials.set(tutorials),
      error: () => this.tutorials.set({}),
    });
  }

  ngOnDestroy(): void {
    this.stop();
    this.stopResultValidityCountdown();
  }

  validate(step: NetcodeValidationStep): void {
    if (step === 'cliente_acceso') {
      this.validateClientAccess();
      return;
    }

    if (this.attempts()[step as 'whatsapp' | 'nombre' | 'pin'] >= 3) {
      this.showToast('Limite de intentos alcanzado');
      return;
    }

    const body = {
      step,
      numero: this.digits(this.whatsapp()),
      nombre_perfil: this.profileName().trim(),
      pin: this.pin().trim(),
    };

    if (step === 'whatsapp' && body.numero.length < 6) {
      this.showToast('Ingresa tu WhatsApp');
      return;
    }
    if (step === 'nombre' && !body.nombre_perfil) {
      this.showToast('Ingresa el nombre del perfil');
      return;
    }
    if (step === 'pin' && !/^\d{4}$/.test(body.pin)) {
      this.showToast('Ingresa un PIN de 4 digitos');
      return;
    }

    this.api.validateAccess(body).subscribe({
      next: (data) => {
        if (data.status !== 'success') {
          this.failed(step, data.message || 'Dato incorrecto.');
          return;
        }

        this.showToast('Validado');
        if (step === 'whatsapp') {
          this.profileOptions.set(data.profiles ?? []);
          this.step.set('nombre');
        }
        if (step === 'nombre') this.step.set('pin');
        if (step === 'pin') {
          this.account.set(data);
          this.step.set('account');
          this.resetCodeSearchState(true);
        }
      },
      error: (error) => {
        this.failed(step, error?.error?.message || 'Dato incorrecto.');
      },
    });
  }

  validateClientAccess(): void {
    const access = this.clientAccess().trim();

    if (!access) {
      this.showToast('Ingresa tu usuario de acceso');
      return;
    }

    const pin = this.clientAccessPin().trim();
    if (!/^\d{4}$/.test(pin)) {
      this.showToast('Ingresa el PIN del perfil');
      return;
    }

    this.api
      .validateAccess({
        step: 'cliente_acceso',
        cliente_acceso_usuario: access,
        pin,
      })
      .subscribe({
        next: (data) => {
          if (data.status !== 'success') {
            this.showToast(data.message || 'Acceso incorrecto.');
            return;
          }

          this.account.set(data);
          this.step.set('account');
          this.showToast('Validado');
          this.resetCodeSearchState(true);
          window.setTimeout(() => this.startAccessCodeSearch(false), 200);
        },
        error: (error) => {
          this.showError('Validacion fallida', error?.error?.message || 'Acceso incorrecto.');
        },
      });
  }

  updatePin(value: string): void {
    this.pin.set(value.replace(/\D+/g, '').slice(0, 4));
  }

  updateClientAccessPin(value: string): void {
    this.clientAccessPin.set(value.replace(/\D+/g, '').slice(0, 4));
  }

  selectProfile(name: string | null): void {
    this.profileName.set((name ?? '').toUpperCase());
  }

  async startAccessCodeSearch(confirmBeforeSearch = true): Promise<void> {
    const accountId = this.account()?.cuenta?.id ?? null;
    const email = this.account()?.cuenta?.email || '';

    if (!email) {
      this.showToast('Primero valida WhatsApp, nombre y PIN');
      return;
    }

    if (this.isSearching()) return;

    if (this.searchAttempt() >= MAX_SEARCH_ATTEMPTS) {
      await this.showFinalNotFound();
      return;
    }

    if (confirmBeforeSearch) {
      const hasExclusiveBot = this.accessCodeBotUrl() !== '';
      const accepted = await Swal.fire({
        title: hasExclusiveBot ? 'Bot exclusivo Netflix' : 'Buscar codigo',
        text: hasExclusiveBot
          ? 'Esta cuenta usa un bot exclusivo para pedir el codigo de acceso. Abre el bot si aun no pediste el codigo y luego inicia la busqueda.'
          : 'Confirma que Netflix ya pidio el codigo, link o login de inicio de sesion.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: hasExclusiveBot ? 'Ya pedi el acceso' : 'Buscar acceso',
        denyButtonText: hasExclusiveBot ? 'Abrir bot exclusivo' : undefined,
        showDenyButton: hasExclusiveBot,
        cancelButtonText: 'Cancelar',
        background: '#111426',
        color: '#fff',
      });

      if (accepted.isDenied) {
        this.openAccessCodeBot();
        return;
      }

      if (!accepted.isConfirmed) return;
    }

    this.startSearch(accountId, email);
  }

  retrySearch(): void {
    if (!this.canRetrySearch()) return;

    const accountId = this.account()?.cuenta?.id ?? null;
    const email = this.account()?.cuenta?.email || '';
    if (!email) {
      this.showToast('Primero valida WhatsApp, nombre y PIN');
      return;
    }

    this.startSearch(accountId, email);
  }

  cancelSearch(): void {
    this.stop();
    this.resetCodeSearchState(false);
    this.viewState.set('access');
    this.step.set('account');
    this.showToast('Busqueda cancelada');
  }

  resetResult(): void {
    this.stop();
    this.resetCodeSearchState(true);
    this.viewState.set('access');
    this.step.set('account');
  }

  async runPrimaryResultAction(): Promise<void> {
    if (!this.resultValue()) return;

    if (this.resultIsLink()) {
      window.open(this.resultValue(), '_blank', 'noopener,noreferrer');
      return;
    }

    await this.copyToClipboard(this.resultValue(), this.resultIsLogin() ? 'Login copiado' : 'Codigo copiado');
  }

  async runSecondaryResultAction(): Promise<void> {
    if (this.resultIsLink()) {
      await this.copyToClipboard(this.resultValue(), 'Link copiado');
      return;
    }

    this.resetResult();
  }

  openAccessCodeBot(): void {
    const url = this.accessCodeBotUrl();
    if (!url) {
      this.showToast('Esta cuenta usa el bot principal');
      return;
    }

    window.open(url, '_blank', 'noopener,noreferrer');
  }

  openTutorial(key: string): void {
    this.tutorialOpen.set(this.tutorialFor(key));
  }

  closeTutorial(): void {
    this.tutorialOpen.set(null);
  }

  attemptsLeft(step: 'whatsapp' | 'nombre' | 'pin'): string {
    const used = this.attempts()[step];
    return used ? `Intentos restantes: ${Math.max(3 - used, 0)}` : '';
  }

  private startSearch(accountId: number | null, email: string): void {
    this.stop();
    this.activeSearch = {
      accountId,
      email,
    };
    this.successfulPollInCurrentSearch = false;
    this.searchAttempt.update((attempt) => Math.min(attempt + 1, MAX_SEARCH_ATTEMPTS));
    this.searchFinishedWithoutResult.set(false);
    this.isSearching.set(true);
    this.resultValue.set('');
    this.resultType.set('');
    this.resultEmail.set('');
    this.resultReceivedAt.set('');
    this.resultProcessedAt.set('');
    this.stopResultValidityCountdown();
    this.timeLeft.set(MAX_TIME);
    this.scanStatus.set('Buscando codigo, link o login reciente...');
    this.viewState.set('scan');

    void Swal.fire({
      title: 'Buscando acceso...',
      text: 'Revisando el ultimo correo valido de la cuenta seleccionada...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      background: '#111426',
      color: '#fff',
      didOpen: () => Swal.showLoading(),
      timer: 1300,
    });

    this.countdown = window.setInterval(() => this.tick(), 1000);
    this.checkServer();
  }

  private failed(step: NetcodeValidationStep, message: string): void {
    const key = step as 'whatsapp' | 'nombre' | 'pin';
    this.attempts.update((current) => ({ ...current, [key]: current[key] + 1 }));
    this.showError('Validacion fallida', message);
  }

  private checkServer(): void {
    if (!this.activeSearch || this.pollingRequest || !this.isSearching()) {
      return;
    }

    const payload = this.activeSearch.accountId
      ? { subject: 'acceso4' as const, account_id: this.activeSearch.accountId }
      : { subject: 'acceso4' as const, email: this.activeSearch.email };

    const request = this.api.searchEmail(payload).subscribe({
      next: async (data) => {
        this.pollingRequest = null;
        this.successfulPollInCurrentSearch = true;

        const result = this.normalizeSearchResult(data);
        if (result !== null) {
          this.stop();
          this.resultValue.set(result.value);
          this.resultType.set(result.type);
          this.resultEmail.set(result.email);
          this.resultReceivedAt.set(result.receivedAt);
          this.resultProcessedAt.set(result.processedAt);
          this.resultValiditySource.set(result.validitySource);
          this.resultExpiresAt.set(result.expiresAt);
          this.startResultValidityCountdown(result.secondsLeft);
          this.searchFinishedWithoutResult.set(false);
          this.isSearching.set(false);
          this.viewState.set('result');
          await this.showSearchResult(result.type, result.value, result.secondsLeft);
          return;
        }

        if (this.isSearching()) {
          this.scheduleNextPoll();
        }
      },
      error: async (error) => {
        this.pollingRequest = null;
        this.stop();
        this.isSearching.set(false);
        this.searchFinishedWithoutResult.set(false);

        if (!this.successfulPollInCurrentSearch) {
          this.searchAttempt.update((attempt) => Math.max(attempt - 1, 0));
        }

        await this.showError(
          'Error de conexion',
          error?.error?.message || 'No pudimos consultar el ultimo acceso de la cuenta.'
        );
        this.viewState.set('access');
        this.step.set('account');
      },
    });

    this.pollingRequest = request.closed ? null : request;
  }

  private scheduleNextPoll(): void {
    if (!this.activeSearch || !this.isSearching()) {
      return;
    }

    if (this.pollingTimer) {
      window.clearTimeout(this.pollingTimer);
    }

    this.pollingTimer = window.setTimeout(() => {
      this.pollingTimer = null;
      this.checkServer();
    }, POLL_MS);
  }

  private normalizeSearchResult(
    data: NetcodeSearchResponse
  ): {
    type: SearchResultType;
    value: string;
    email: string;
    receivedAt: string;
    processedAt: string;
    expiresAt: string;
    secondsLeft: number;
    validitySource: 'processed_at' | 'received_at';
  } | null {
    const value = String(data.value ?? data.valor_extraido ?? '').trim();
    const type = (data.type ?? data.tipo ?? '') as SearchResultType | '';
    const secondsLeft = Math.max(0, Math.min(420, Number(data.seconds_remaining ?? 0)));

    if (data.status !== 'success' || !value || !type || secondsLeft <= 0) {
      return null;
    }

    return {
      type,
      value,
      email: data.email || '',
      receivedAt: data.received_at || data.fecha || '',
      processedAt: data.processed_at || '',
      expiresAt: data.expires_at || '',
      secondsLeft,
      validitySource: data.validity_source === 'received_at' ? 'received_at' : 'processed_at',
    };
  }

  private tick(): void {
    const current = this.timeLeft();
    this.timeLeft.set(Math.max(current, 0));
    if (current === 30) this.scanStatus.set('Revisando el ultimo correo valido de la cuenta...');
    if (current === 12) this.scanStatus.set('Ultima busqueda de este intento...');
    if (current <= 0) {
      this.stop();
      this.isSearching.set(false);
      this.searchFinishedWithoutResult.set(true);
      this.viewState.set('access');
      this.step.set('account');
      void this.handleSearchTimeout();
      return;
    }
    this.timeLeft.set(current - 1);
  }

  private stop(): void {
    if (this.countdown) window.clearInterval(this.countdown);
    if (this.pollingTimer) window.clearTimeout(this.pollingTimer);
    this.pollingRequest?.unsubscribe();
    this.countdown = null;
    this.pollingTimer = null;
    this.pollingRequest = null;
    this.activeSearch = null;
  }

  private resetCodeSearchState(resetAttempts: boolean): void {
    this.resultValue.set('');
    this.resultType.set('');
    this.resultEmail.set('');
    this.resultReceivedAt.set('');
    this.resultProcessedAt.set('');
    this.resultValiditySource.set('processed_at');
    this.resultSecondsLeft.set(0);
    this.resultExpiresAt.set('');
    this.stopResultValidityCountdown();
    this.searchFinishedWithoutResult.set(false);
    this.isSearching.set(false);
    this.successfulPollInCurrentSearch = false;
    this.timeLeft.set(MAX_TIME);
    this.scanStatus.set('Buscando correo reciente...');
    if (resetAttempts) this.searchAttempt.set(0);
  }

  private async handleSearchTimeout(): Promise<void> {
    if (this.searchAttempt() < MAX_SEARCH_ATTEMPTS) {
      const response = await Swal.fire({
        title: 'No encontramos un acceso valido',
        text: 'Puedes realizar una segunda busqueda.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reintentar busqueda',
        cancelButtonText: 'Cerrar',
        background: '#111426',
        color: '#fff',
      });

      if (response.isConfirmed) {
        this.retrySearch();
      }

      return;
    }

    await this.showFinalNotFound();
  }

  private async showFinalNotFound(): Promise<void> {
    await Swal.fire({
      title: 'No encontramos un acceso valido',
      text: 'Se realizaron las 2 busquedas disponibles.',
      icon: 'error',
      confirmButtonText: 'Entendido',
      background: '#111426',
      color: '#fff',
    });
  }

  private async showSearchResult(type: SearchResultType, value: string, secondsLeft: number): Promise<void> {
    let modalSeconds = Math.max(0, secondsLeft);
    let modalInterval: number | null = null;

    const title = type === 'link' ? 'LINK ENCONTRADO' : type === 'login' ? 'LOGIN ENCONTRADO' : 'CODIGO ENCONTRADO';
    const confirmButtonText = type === 'link' ? 'Abrir link' : type === 'login' ? 'Copiar login' : 'Copiar codigo';
    const showDenyButton = type === 'link';
    const resultLabel = type === 'link' ? 'El link ya esta visible en pantalla.' : type === 'login' ? 'El login ya esta visible en pantalla.' : 'El codigo ya esta visible en pantalla.';

    const response = await Swal.fire({
      title,
      html: `
        <div style="font-size:16px;font-weight:700;color:#35f7a4;word-break:break-word">
          ${this.escapeHtml(resultLabel)}
        </div>
        <div style="margin-top:12px;color:#ffd166;font-weight:900">Vigente por <span id="swal-code-timer">${this.formatSeconds(modalSeconds)}</span></div>
        <div style="margin-top:6px;color:rgba(255,255,255,.68);font-size:13px">El resultado dura 7 minutos desde que fue ${this.resultValiditySource() === 'processed_at' ? 'procesado' : 'recibido'}.</div>
      `,
      icon: 'success',
      confirmButtonText,
      denyButtonText: showDenyButton ? 'Copiar link' : undefined,
      showDenyButton,
      background: '#111426',
      color: '#fff',
      didOpen: () => {
        modalInterval = window.setInterval(() => {
          modalSeconds = Math.max(0, modalSeconds - 1);
          const timer = document.getElementById('swal-code-timer');
          if (timer) timer.textContent = this.formatSeconds(modalSeconds);
          if (modalSeconds <= 0 && modalInterval) {
            window.clearInterval(modalInterval);
            modalInterval = null;
          }
        }, 1000);
      },
      willClose: () => {
        if (modalInterval) window.clearInterval(modalInterval);
      },
    });

    if (response.isConfirmed) {
      if (type === 'link') {
        window.open(value, '_blank', 'noopener,noreferrer');
      } else {
        await this.copyToClipboard(value, type === 'login' ? 'Login copiado' : 'Codigo copiado');
      }
    }

    if (response.isDenied && type === 'link') {
      await this.copyToClipboard(value, 'Link copiado');
    }
  }

  private async showError(title: string, message: string): Promise<void> {
    await Swal.fire({
      title,
      text: message,
      icon: 'error',
      confirmButtonText: 'Entendido',
      background: '#111426',
      color: '#fff',
    });
  }

  private escapeHtml(value: string): string {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  private startResultValidityCountdown(seconds: number): void {
    this.stopResultValidityCountdown();
    this.resultSecondsLeft.set(Math.max(0, seconds));
    this.resultValidityCountdown = window.setInterval(() => {
      const next = Math.max(0, this.resultSecondsLeft() - 1);
      this.resultSecondsLeft.set(next);
      if (next <= 0) this.stopResultValidityCountdown();
    }, 1000);
  }

  private stopResultValidityCountdown(): void {
    if (this.resultValidityCountdown) window.clearInterval(this.resultValidityCountdown);
    this.resultValidityCountdown = null;
  }

  private formatSeconds(seconds: number): string {
    const safeSeconds = Math.max(0, Math.floor(seconds));
    const minutes = Math.floor(safeSeconds / 60)
      .toString()
      .padStart(2, '0');
    const remainder = (safeSeconds % 60)
      .toString()
      .padStart(2, '0');

    return `${minutes}:${remainder}`;
  }

  private formatBackendDateLabel(value: string): string {
    const match = value.match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2}:\d{2})$/);
    if (!match) {
      return value;
    }

    return `${match[3]}/${match[2]} ${match[4]}`;
  }

  private tutorialFor(key: string): Tutorial {
    const defaults: Record<string, Tutorial> = {
      whatsapp: {
        title: 'Tutorial WhatsApp',
        steps: [
          'Ingresa el numero del cliente tal como esta en el Excel.',
          'Puedes escribirlo con espacios; el sistema compara solo numeros.',
          'Si falla, revisa que hayas presionado Leer Excel ahora despues del cambio.',
        ],
        media_url: null,
        media_type: null,
      },
      cliente_acceso: {
        title: 'Tutorial acceso directo',
        steps: [
          'Ingresa tu usuario de acceso.',
          'Ingresa el PIN del perfil.',
          'Si ambos coinciden, podras buscar el codigo de 4 digitos sin WhatsApp ni nombre.',
        ],
        media_url: null,
        media_type: null,
      },
      nombre: {
        title: 'Tutorial perfil',
        steps: [
          'Escribe el nombre exacto del perfil.',
          'El sistema ignora mayusculas, espacios y tildes.',
          'Si no valida, revisa que ese perfil pertenezca al WhatsApp ingresado.',
        ],
        media_url: null,
        media_type: null,
      },
      pin: {
        title: 'Tutorial PIN',
        steps: [
          'Ingresa el PIN del perfil.',
          'Si coincide, se mostraran correo, contrasena, vencimiento y datos completos.',
          'Luego presiona Buscar codigo de 4 digitos.',
        ],
        media_url: null,
        media_type: null,
      },
      acceso4: {
        title: 'Tutorial codigo de acceso 4 digitos',
        steps: [
          'Valida WhatsApp, perfil y PIN.',
          'Confirma que Netflix ya pidio el codigo, link o login de inicio de sesion.',
          'Presiona Buscar codigo de 4 digitos.',
          'Copia o abre el resultado antes de que venza.',
        ],
        media_url: null,
        media_type: null,
      },
    };

    const stored = this.tutorials()[key];
    if (!stored) return defaults[key] ?? defaults['acceso4'];

    return {
      ...defaults[key],
      title: stored.title || defaults[key]?.title || 'Tutorial',
      steps: stored.steps?.length ? stored.steps : defaults[key]?.steps ?? [],
      media_url: stored.media_url,
      media_type: stored.media_type,
    };
  }

  private digits(value: string): string {
    return value.replace(/\D+/g, '');
  }

  private async copyToClipboard(value: string, successMessage: string): Promise<void> {
    try {
      await navigator.clipboard?.writeText(value);
      this.showToast(successMessage);
    } catch {
      this.showToast(value);
    }
  }

  private showToast(message: string): void {
    this.toast.set(message);
    window.setTimeout(() => this.toast.set(''), 1800);
  }
}
