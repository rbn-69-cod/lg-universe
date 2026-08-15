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

type AccessStep = 'whatsapp' | 'nombre' | 'pin' | 'account';
type ViewState = 'access' | 'scan' | 'result';

const MAX_TIME = 60;
const POLL_MS = 4000;

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

  private countdown: number | null = null;
  private polling: number | null = null;

  readonly resultIsLink = computed(() => /^https?:\/\//i.test(this.resultValue()));

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
        window.setTimeout(() => this.startAccessCodeSearch(false), 200);
      },
      error: (error) => {
        window.alert(`Validacion fallida\n\n${error?.error?.message || 'Acceso incorrecto.'}`);
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

  startAccessCodeSearch(confirmBeforeSearch = true): void {
    const email = this.account()?.cuenta?.email;
    if (!email) {
      this.showToast('Primero valida WhatsApp, nombre y PIN');
      return;
    }

    if (confirmBeforeSearch) {
      const accepted = window.confirm('Inicio sesion codigo 4 digitos\n\nConfirma que Netflix ya pidio el codigo de login.');
      if (!accepted) return;
    }

    this.resultValue.set('');
    this.resultType.set('');
    this.timeLeft.set(MAX_TIME);
    this.scanStatus.set('Buscando inicio sesion codigo 4 digitos...');
    this.viewState.set('scan');

    this.tick();
    this.checkServer(email);
    this.countdown = window.setInterval(() => this.tick(), 1000);
    this.polling = window.setInterval(() => this.checkServer(email), POLL_MS);
  }

  cancelSearch(): void {
    this.stop();
    this.resultValue.set('');
    this.viewState.set('access');
    this.step.set('account');
    this.showToast('Busqueda cancelada');
  }

  resetResult(): void {
    this.stop();
    this.resultValue.set('');
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
      .catch(() => window.alert(value));
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
    window.alert(`Validacion fallida\n\n${message}`);
  }

  private checkServer(email: string): void {
    this.api.searchEmail(email, 'acceso4').subscribe({
      next: (data) => {
        if (data.status === 'success' && data.valor_extraido) {
          this.stop();
          this.resultValue.set(String(data.valor_extraido).trim());
          this.resultType.set(data.tipo || '');
          this.viewState.set('result');
        }
      },
      error: () => {
        // Existing screen keeps polling silently on transient errors.
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
      window.alert('No encontrado\n\nReenvia el correo desde Netflix e intenta otra vez.');
      this.resetResult();
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
