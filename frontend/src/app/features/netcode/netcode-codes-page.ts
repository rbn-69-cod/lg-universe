import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import {
  LucideArrowLeft,
  LucideCirclePlay,
  LucideCopy,
  LucideExternalLink,
  LucideHome,
  LucideImage,
  LucideKey,
  LucideMail,
  LucideRefreshCcw,
} from '@lucide/angular';

import { NetcodeApi, NetcodeSearchSubject, Tutorial } from './netcode-api';
import { LgMonogramLogo } from '../../shared/lg-monogram-logo';

type Mode = 'hogar' | 'temporal';
type ViewState = 'form' | 'scan' | 'result';

const MAX_TIME = 60;
const POLL_MS = 4000;

@Component({
  selector: 'app-netcode-codes-page',
  imports: [
    FormsModule,
    LucideArrowLeft,
    LucideCirclePlay,
    LucideCopy,
    LucideExternalLink,
    LucideHome,
    LucideImage,
    LucideKey,
    LucideMail,
    LucideRefreshCcw,
    LgMonogramLogo,
  ],
  templateUrl: './netcode-codes-page.html',
  styleUrl: './netcode-codes-page.css',
})
export class NetcodeCodesPage {
  private readonly api = inject(NetcodeApi);

  readonly email = signal('');
  readonly selectedMode = signal<Mode | null>(null);
  readonly state = signal<ViewState>('form');
  readonly timeLeft = signal(MAX_TIME);
  readonly scanStatus = signal('Buscando correo reciente...');
  readonly resultValue = signal('');
  readonly resultType = signal('');
  readonly toast = signal('');
  readonly tutorials = signal<Record<string, Tutorial>>({});
  readonly tutorialOpen = signal<Tutorial | null>(null);

  private countdown: number | null = null;
  private polling: number | null = null;

  readonly modeTitle = computed(() => {
    if (this.selectedMode() === 'hogar') return 'Code hogar';
    if (this.selectedMode() === 'temporal') return 'Code temporal';
    return 'Actualizar Hogar - Code Temporal';
  });

  readonly modeText = computed(() => {
    if (this.selectedMode() === 'hogar') {
      return 'Ingresa el correo de Netflix y presiona Code hogar para buscar el enlace de actualizacion de hogar.';
    }

    if (this.selectedMode() === 'temporal') {
      return 'Ingresa el correo de Netflix y presiona Code temporal para buscar el codigo o enlace temporal.';
    }

    return 'Esta pestana es solo para Code hogar y Code temporal. Inicio sesion de 4 digitos esta separado.';
  });

  readonly resultIsLink = computed(() => /^https?:\/\//i.test(this.resultValue()));

  constructor() {
    const mode = new URLSearchParams(window.location.search).get('modo');
    if (mode === 'hogar' || mode === 'temporal') {
      this.selectedMode.set(mode);
    }

    this.api.tutorials().subscribe({
      next: (tutorials) => this.tutorials.set(tutorials),
      error: () => this.tutorials.set({}),
    });
  }

  selectMode(mode: Mode): void {
    this.selectedMode.set(mode);
  }

  start(mode: Mode): void {
    this.selectedMode.set(mode);
    const currentEmail = this.email().trim().toLowerCase();

    if (!this.validEmail(currentEmail)) {
      this.showToast('Ingresa el correo de Netflix');
      return;
    }

    const label = mode === 'hogar' ? 'Code hogar' : 'Code temporal';
    const accepted = window.confirm(`${label}\n\nConfirma que Netflix ya envio el correo.`);
    if (!accepted) return;

    this.email.set(currentEmail);
    this.resultValue.set('');
    this.resultType.set('');
    this.timeLeft.set(MAX_TIME);
    this.scanStatus.set(`Buscando ${label.toLowerCase()}...`);
    this.state.set('scan');

    this.tick();
    this.checkServer(mode);
    this.countdown = window.setInterval(() => this.tick(), 1000);
    this.polling = window.setInterval(() => this.checkServer(mode), POLL_MS);
  }

  cancelSearch(): void {
    this.stop();
    this.resultValue.set('');
    this.state.set('form');
    this.showToast('Busqueda cancelada');
  }

  changeSearchData(): void {
    this.stop();
    this.resultValue.set('');
    this.state.set('form');
    this.showToast('Cambia el correo y busca de nuevo');
  }

  reset(): void {
    this.stop();
    this.resultValue.set('');
    this.state.set('form');
  }

  copyOrOpen(): void {
    const value = this.resultValue();
    if (!value) return;

    if (/^https?:\/\//i.test(value)) {
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

  tutorialFor(key: string): Tutorial {
    const defaults: Record<string, Tutorial> = {
      hogar: {
        title: 'Tutorial link de hogar',
        steps: [
          'Ingresa el correo Netflix de la cuenta.',
          'En Netflix pide actualizar hogar.',
          'Presiona Code hogar.',
          'Cuando salga el enlace, abrelo y confirma rapido.',
        ],
        media_url: null,
        media_type: null,
      },
      temporal: {
        title: 'Tutorial codigo temporal',
        steps: [
          'Ingresa el correo Netflix de la cuenta.',
          'Pide a Netflix enviar el codigo temporal.',
          'Presiona Code temporal.',
          'Copia el codigo o abre el enlace que aparezca.',
        ],
        media_url: null,
        media_type: null,
      },
      general: {
        title: 'Tutorial NetCode',
        steps: [
          'Elige la pestana correcta.',
          'Confirma que Netflix ya envio el correo.',
          'Presiona buscar y espera hasta 60 segundos.',
        ],
        media_url: null,
        media_type: null,
      },
    };

    const stored = this.tutorials()[key];
    if (!stored) return defaults[key] ?? defaults['general'];

    return {
      ...defaults[key],
      title: stored.title || defaults[key]?.title || 'Tutorial',
      steps: stored.steps?.length ? stored.steps : defaults[key]?.steps ?? [],
      media_url: stored.media_url,
      media_type: stored.media_type,
    };
  }

  private checkServer(mode: NetcodeSearchSubject): void {
    this.api.searchEmail({ email: this.email(), subject: mode }).subscribe({
      next: (data) => {
        if (data.status === 'success' && data.valor_extraido) {
          this.showResult(String(data.valor_extraido), data.tipo || '');
        }
      },
      error: () => {
        // The existing screen keeps polling silently on transient errors.
      },
    });
  }

  private showResult(value: string, type: string): void {
    this.stop();
    this.resultValue.set(value.trim());
    this.resultType.set(type);
    this.state.set('result');
  }

  private tick(): void {
    const current = this.timeLeft();
    this.timeLeft.set(Math.max(current, 0));

    if (current === 30) this.scanStatus.set('Revisando correos recientes...');
    if (current === 12) this.scanStatus.set('Ultima busqueda...');

    if (current <= 0) {
      this.stop();
      window.alert('No encontrado\n\nReenvia el correo desde Netflix e intenta otra vez.');
      this.reset();
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

  private validEmail(value: string): boolean {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  private showToast(message: string): void {
    this.toast.set(message);
    window.setTimeout(() => this.toast.set(''), 1800);
  }
}
