import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
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
  NetcodeValidationStep,
  NetflixProfileValidationResponse,
  Tutorial,
} from './netcode-api';
import Swal from 'sweetalert2';

type AccessStep = 'whatsapp' | 'nombre' | 'pin' | 'account';
type ViewState = 'access' | 'scan' | 'result';

const MAX_TIME = 60;
const POLL_MS = 4000;
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
  ],
  templateUrl: './netcode-access-page.html',
  styleUrl: './netcode-codes-page.css',
})
export class NetcodeAccessPage {
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
  readonly resultType = signal('');
  readonly searchAttempt = signal(0);
  readonly searchFinishedWithoutResult = signal(false);
  readonly isSearching = signal(false);

  private countdown: number | null = null;
  private polling: number | null = null;
  private successfulPollInCurrentSearch = false;

  readonly resultIsLink = computed(() => /^https?:\/\//i.test(this.resultValue()));
  readonly accessCodeBotUrl = computed(() => this.account()?.cuenta?.bot_acceso4_masked_url || '');
  readonly searchAttemptLabel = computed(() => `Busqueda ${Math.max(this.searchAttempt(), 1)} de ${MAX_SEARCH_ATTEMPTS}`);
  readonly canRetrySearch = computed(
    () =>
      this.searchFinishedWithoutResult() &&
      !this.isSearching() &&
      !this.resultValue() &&
      this.searchAttempt() > 0 &&
      this.searchAttempt() < MAX_SEARCH_ATTEMPTS
  );

  constructor() {
    this.api.tutorials().subscribe({
      next: (tutorials) => this.tutorials.set(tutorials),
      error: () => this.tutorials.set({}),
    });
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

    this.api.validateAccess({
      step: 'cliente_acceso',
      cliente_acceso_usuario: access,
      pin,
    }).subscribe({
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
    const email = this.account()?.cuenta?.email;
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
          : 'Confirma que Netflix ya pidio el codigo de login.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: hasExclusiveBot ? 'Ya pedi el codigo' : 'Buscar codigo',
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

    this.startSearch(email);
  }

  retrySearch(): void {
    if (!this.canRetrySearch()) return;

    const email = this.account()?.cuenta?.email;
    if (!email) {
      this.showToast('Primero valida WhatsApp, nombre y PIN');
      return;
    }

    this.startSearch(email);
  }

  private startSearch(email: string): void {
    this.stop();
    this.successfulPollInCurrentSearch = false;
    this.searchAttempt.update((attempt) => Math.min(attempt + 1, MAX_SEARCH_ATTEMPTS));
    this.searchFinishedWithoutResult.set(false);
    this.isSearching.set(true);
    this.resultValue.set('');
    this.resultType.set('');
    this.timeLeft.set(MAX_TIME);
    this.scanStatus.set('Buscando inicio sesion codigo 4 digitos...');
    this.viewState.set('scan');

    void Swal.fire({
      title: 'Buscando codigo...',
      text: 'Revisando correos recientes...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      background: '#111426',
      color: '#fff',
      didOpen: () => Swal.showLoading(),
      timer: 1300,
    });

    this.countdown = window.setInterval(() => this.tick(), 1000);
    this.polling = window.setInterval(() => this.checkServer(email), POLL_MS);
    this.tick();
    this.checkServer(email);
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

  copyOrOpen(): void {
    const value = this.resultValue();
    if (!value) return;
    if (this.resultIsLink()) {
      window.open(value, '_blank', 'noopener,noreferrer');
      return;
    }

    navigator.clipboard
      ?.writeText(value)
      .then(() => this.showToast('Copiado'))
      .catch(() => this.showCodeFound(value));
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

  private failed(step: NetcodeValidationStep, message: string): void {
    const key = step as 'whatsapp' | 'nombre' | 'pin';
    this.attempts.update((current) => ({ ...current, [key]: current[key] + 1 }));
    this.showError('Validacion fallida', message);
  }

  private checkServer(email: string): void {
    this.api.searchEmail(email, 'acceso4').subscribe({
      next: (data) => {
        this.successfulPollInCurrentSearch = true;
        if (data.status === 'success' && data.valor_extraido) {
          this.stop();
          const value = String(data.valor_extraido).trim();
          this.resultValue.set(value);
          this.resultType.set(data.tipo || '');
          this.searchFinishedWithoutResult.set(false);
          this.isSearching.set(false);
          this.viewState.set('result');
          void this.showCodeFound(value);
        }
      },
      error: async () => {
        this.stop();
        this.isSearching.set(false);
        this.searchFinishedWithoutResult.set(false);

        if (!this.successfulPollInCurrentSearch) {
          this.searchAttempt.update((attempt) => Math.max(attempt - 1, 0));
        }

        await this.showError('Error de conexion', 'No pudimos consultar los codigos. Intentalo nuevamente.');
        this.viewState.set('access');
        this.step.set('account');
      },
    });
  }

  private tick(): void {
    const current = this.timeLeft();
    this.timeLeft.set(Math.max(current, 0));
    if (current === 30) this.scanStatus.set('Revisando correos recientes...');
    if (current === 12) this.scanStatus.set('Ultima busqueda...');
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
    if (this.polling) window.clearInterval(this.polling);
    this.countdown = null;
    this.polling = null;
  }

  private resetCodeSearchState(resetAttempts: boolean): void {
    this.resultValue.set('');
    this.resultType.set('');
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
        title: 'No encontramos el codigo',
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
        return;
      }

      return;
    }

    await this.showFinalNotFound();
  }

  private async showFinalNotFound(): Promise<void> {
    await Swal.fire({
      title: 'No encontramos un codigo valido',
      text: 'Se realizaron las 2 busquedas disponibles.',
      icon: 'error',
      confirmButtonText: 'Entendido',
      background: '#111426',
      color: '#fff',
    });
  }

  private async showCodeFound(value: string): Promise<void> {
    const response = await Swal.fire({
      title: 'Codigo encontrado',
      html: `<div style="font-size:44px;font-weight:900;letter-spacing:.18em;color:#35f7a4">${this.escapeHtml(value)}</div>`,
      icon: 'success',
      confirmButtonText: this.resultIsLink() ? 'Cerrar' : 'Copiar codigo',
      background: '#111426',
      color: '#fff',
    });

    if (response.isConfirmed && !this.resultIsLink()) {
      await navigator.clipboard?.writeText(value).catch(() => undefined);
      this.showToast('Copiado');
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
          'Confirma que Netflix ya pidio el codigo de inicio de sesion.',
          'Presiona Buscar codigo de 4 digitos.',
          'Copia el codigo antes de que venza.',
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

  private showToast(message: string): void {
    this.toast.set(message);
    window.setTimeout(() => this.toast.set(''), 1800);
  }
}
