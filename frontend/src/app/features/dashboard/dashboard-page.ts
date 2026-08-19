import { DatePipe, DecimalPipe, NgClass } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import Swal from 'sweetalert2';
import {
  LucideBookOpen,
  LucideFileSpreadsheet,
  LucideHome,
  LucideInbox,
  LucideLogOut,
  LucideRefreshCcw,
  LucideSettings,
  LucideShieldCheck,
} from '@lucide/angular';

import {
  AccountBotLinksPayload,
  DashboardAccount,
  DashboardAdmin,
  DashboardAdminPayload,
  DashboardApi,
  DashboardCatalogDuration,
  DashboardCatalogPayload,
  DashboardCatalogPlatform,
  DashboardData,
  DashboardImapItem,
  DashboardImapSettingsPayload,
  DashboardPaymentMethod,
  DashboardPaymentSettings,
  DashboardProfile,
  DashboardRange,
  DashboardRangePayload,
  DashboardTutorial,
} from './dashboard-api';

type DashboardSection = 'overview' | 'catalog' | 'payments' | 'excel' | 'accounts' | 'profiles' | 'ranges' | 'imap' | 'tutorials' | 'admins';

interface PlatformRangeGroup {
  plataforma: string;
  ranges: DashboardRange[];
  activeCount: number;
  lastSync: string | null;
}

interface PlatformSummary {
  plataforma: string;
  accounts: number;
  profiles: number;
}

@Component({
  selector: 'app-dashboard-page',
  imports: [
    DatePipe,
    DecimalPipe,
    FormsModule,
    NgClass,
    LucideBookOpen,
    LucideFileSpreadsheet,
    LucideHome,
    LucideInbox,
    LucideLogOut,
    LucideRefreshCcw,
    LucideSettings,
    LucideShieldCheck,
  ],
  templateUrl: './dashboard-page.html',
  styleUrl: './dashboard-page.css',
})
export class DashboardPage {
  private readonly api = inject(DashboardApi);

  readonly data = signal<DashboardData | null>(null);
  readonly loading = signal(true);
  readonly syncing = signal(false);
  readonly runningImap = signal(false);
  readonly testingImap = signal(false);
  readonly savingImap = signal(false);
  readonly savingTutorialKey = signal<string | null>(null);
  readonly savingCatalog = signal(false);
  readonly savingAdmin = signal(false);
  readonly savingPayments = signal(false);
  readonly uploadingPaymentQr = signal<number | null>(null);
  readonly clearingData = signal(false);
  readonly clearingDashboardOnlyEmails = signal(false);
  readonly savingRange = signal(false);
  readonly savingAccountBots = signal(false);
  readonly syncingRangeId = signal<number | null>(null);
  readonly editingRangeId = signal<number | null>(null);
  readonly editingCatalogId = signal<number | null>(null);
  readonly editingAdminId = signal<number | null>(null);
  readonly editingAccountId = signal<number | null>(null);
  readonly activeSection = signal<DashboardSection>('overview');
  readonly selectedPlatform = signal('Netflix');
  readonly message = signal('');
  readonly error = signal('');
  readonly rangeForm = signal<DashboardRangePayload>(this.emptyRange());
  readonly catalogForm = signal<DashboardCatalogPayload>(this.emptyCatalog());
  readonly adminForm = signal<DashboardAdminPayload>(this.emptyAdmin());
  readonly paymentForm = signal<DashboardPaymentSettings>(this.emptyPaymentSettings());
  readonly clearConfirmation = signal('');
  readonly accountBotForm = signal<AccountBotLinksPayload>(this.emptyAccountBotLinks());
  readonly imapForm = signal<DashboardImapSettingsPayload>(this.emptyImapSettings());
  readonly imapOutput = signal('');
  readonly imapTestResult = signal<{
    criteria: string;
    found: number;
    latest: Array<{ uid: number; subject: string; from: string | null; date: string | null }>;
  } | null>(null);
  readonly tutorialForm = signal<Record<string, { title: string; steps: string; media: File | null }>>({});

  readonly stats = computed(() => this.data()?.stats);
  readonly accounts = computed(() => this.data()?.accounts ?? []);
  readonly profiles = computed(() => this.data()?.profiles ?? []);
  readonly ranges = computed(() => this.data()?.ranges ?? []);
  readonly catalog = computed(() => this.data()?.catalog ?? []);
  readonly admins = computed(() => this.data()?.admins ?? []);
  readonly paymentSettings = computed(() => this.data()?.payment_settings ?? this.emptyPaymentSettings());
  readonly imap = computed(() => this.data()?.imap ?? null);
  readonly imapClientVisibleItems = computed(() => this.imap()?.client_visible_items ?? []);
  readonly imapDashboardOnlyItems = computed(() => this.imap()?.dashboard_only_items ?? []);
  readonly tutorials = computed(() => this.data()?.tutorials ?? {});
  readonly tutorialLabels = computed(() => this.data()?.tutorial_labels ?? {});
  readonly tutorialEntries = computed(() => Object.entries(this.tutorials()));
  readonly platforms = computed(() => {
    const names = new Set<string>();

    for (const account of this.accounts()) if (account.source_platforma) names.add(account.source_platforma);
    for (const profile of this.profiles()) if (profile.source_platforma) names.add(profile.source_platforma);
    for (const range of this.ranges()) if (range.plataforma) names.add(range.plataforma);

    return Array.from(names).sort((a, b) => a.localeCompare(b));
  });
  readonly platformAccounts = computed(() => this.accounts().filter((account) => account.source_platforma === this.selectedPlatform()));
  readonly platformProfiles = computed(() => this.profiles().filter((profile) => profile.source_platforma === this.selectedPlatform()));
  readonly platformRanges = computed(() => this.ranges().filter((range) => range.plataforma === this.selectedPlatform()));
  readonly platformSummaries = computed<PlatformSummary[]>(() => {
    const accountCounts = new Map<string, number>();
    const profileCounts = new Map<string, number>();

    for (const account of this.accounts()) {
      if (account.source_platforma) {
        accountCounts.set(account.source_platforma, (accountCounts.get(account.source_platforma) ?? 0) + 1);
      }
    }

    for (const profile of this.profiles()) {
      if (profile.source_platforma) {
        profileCounts.set(profile.source_platforma, (profileCounts.get(profile.source_platforma) ?? 0) + 1);
      }
    }

    return this.platforms().map((plataforma) => ({
      plataforma,
      accounts: accountCounts.get(plataforma) ?? 0,
      profiles: profileCounts.get(plataforma) ?? 0,
    }));
  });
  readonly platformStats = computed(() => {
    const accounts = this.platformAccounts();
    const profiles = this.platformProfiles();

    return {
      cuentas: accounts.length,
      perfiles: profiles.length,
      ocupados: profiles.filter((profile) => profile.ocupado).length,
      disponibles: profiles.filter((profile) => !profile.ocupado).length,
      vencidos: profiles.filter((profile) => (profile.estado_excel || '').toLowerCase().includes('vencido')).length,
      lastSync: this.platformRanges()
        .map((range) => range.ultimo_sync_at)
        .filter((value): value is string => Boolean(value))
        .sort()
        .at(-1) ?? null,
    };
  });
  readonly platformGroups = computed<PlatformRangeGroup[]>(() => {
    const groups = new Map<string, DashboardRange[]>();

    for (const range of this.ranges()) {
      const key = range.plataforma || 'Sin plataforma';
      groups.set(key, [...(groups.get(key) ?? []), range]);
    }

    return Array.from(groups.entries())
      .map(([plataforma, ranges]) => ({
        plataforma,
        ranges,
        activeCount: ranges.filter((range) => range.activo).length,
        lastSync: ranges
          .map((range) => range.ultimo_sync_at)
          .filter((value): value is string => Boolean(value))
          .sort()
          .at(-1) ?? null,
      }))
      .sort((a, b) => a.plataforma.localeCompare(b.plataforma));
  });
  readonly sectionTitle = computed(() => {
    const titles: Record<DashboardSection, string> = {
      overview: 'Resumen',
      catalog: 'Catalogo',
      payments: 'Pagos / QR',
      excel: 'Tablas Excel',
      accounts: 'Cuentas',
      profiles: 'Perfiles',
      ranges: 'Rangos',
      imap: 'IMAP / Cron',
      tutorials: 'Tutoriales',
      admins: 'Admins',
    };

    return titles[this.activeSection()];
  });

  constructor() {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.error.set('');

    this.api.get().subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillImapForm();
        this.fillTutorialForm();
        this.fillPaymentForm();
        if (!this.platforms().includes(this.selectedPlatform())) {
          this.selectedPlatform.set(this.platforms()[0] ?? 'Netflix');
        }
        this.loading.set(false);
      },
      error: (error) => {
        this.error.set(error?.error?.message || 'No se pudo cargar el dashboard.');
        this.loading.set(false);
      },
    });
  }

  syncExcel(rangeId?: number, plataforma?: string): void {
    if (this.syncing()) return;

    this.syncing.set(true);
    this.syncingRangeId.set(rangeId ?? null);
    this.message.set('');
    this.error.set('');

    const platformToSync = rangeId ? plataforma : plataforma ?? this.selectedPlatform();

    this.api.syncExcel({ rangeId, plataforma: platformToSync }).subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillImapForm();
        this.fillTutorialForm();
        this.fillPaymentForm();
        this.message.set(rangeId ? 'Tabla sincronizada correctamente.' : platformToSync ? `Plataforma ${platformToSync} sincronizada.` : response.message);
        this.syncing.set(false);
        this.syncingRangeId.set(null);
      },
      error: (error) => {
        this.error.set(error?.error?.message || 'No se pudo leer el Excel.');
        this.syncing.set(false);
        this.syncingRangeId.set(null);
      },
    });
  }

  showSection(section: DashboardSection): void {
    this.activeSection.set(section);
  }

  updateTutorialField(key: string, field: 'title' | 'steps', value: string): void {
    this.tutorialForm.update((current) => ({
      ...current,
      [key]: {
        ...(current[key] ?? { title: '', steps: '', media: null }),
        [field]: value,
      },
    }));
  }

  editCatalog(platform: DashboardCatalogPlatform): void {
    this.activeSection.set('catalog');
    this.editingCatalogId.set(platform.id);
    this.catalogForm.set({
      nombre: platform.nombre,
      imagen: platform.imagen || '',
      precio: Number(platform.precio || 0),
      descripcion: platform.descripcion || '',
      features: (platform.features || []).join('\n'),
      activacion: platform.activacion || '',
      terminos: platform.terminos || '',
      activo: platform.activo,
      duraciones: this.normalizeCatalogDurations(platform.duraciones, Number(platform.precio || 0)),
    });
  }

  saveCatalog(): void {
    if (this.savingCatalog()) return;

    const request = this.editingCatalogId()
      ? this.api.updateCatalogPlatform(this.editingCatalogId() as number, this.catalogForm())
      : this.api.createCatalogPlatform(this.catalogForm());

    this.savingCatalog.set(true);
    this.message.set('');
    this.error.set('');

    request.subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.message.set(this.editingCatalogId() ? 'Plataforma actualizada.' : 'Plataforma creada.');
        this.cancelCatalogEdit();
        this.savingCatalog.set(false);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudo guardar la plataforma.');
        this.savingCatalog.set(false);
      },
    });
  }

  deleteCatalog(platform: DashboardCatalogPlatform): void {
    this.confirmDanger('Eliminar plataforma', `Se eliminara ${platform.nombre}.`).then((confirmed) => {
      if (!confirmed) return;

      this.api.deleteCatalogPlatform(platform.id).subscribe({
        next: (response) => {
          this.data.set(response.data);
          this.success('Plataforma eliminada.');
        },
        error: (error) => this.fail(error?.error?.message || 'No se pudo eliminar la plataforma.'),
      });
    });
  }

  moveCatalog(platform: DashboardCatalogPlatform, direction: 'up' | 'down'): void {
    this.api.moveCatalogPlatform(platform.id, direction).subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.message.set('Orden actualizado.');
      },
      error: (error) => this.error.set(error?.error?.message || 'No se pudo ordenar.'),
    });
  }

  cancelCatalogEdit(): void {
    this.editingCatalogId.set(null);
    this.catalogForm.set(this.emptyCatalog());
  }

  updateCatalogField<K extends keyof DashboardCatalogPayload>(field: K, value: DashboardCatalogPayload[K]): void {
    this.catalogForm.update((current) => ({ ...current, [field]: value }));
  }

  updateCatalogDuration(index: number, field: keyof DashboardCatalogDuration, value: string | number | boolean): void {
    this.catalogForm.update((current) => ({
      ...current,
      duraciones: current.duraciones.map((duration, currentIndex) => currentIndex === index
        ? {
            ...duration,
            [field]: field === 'precio'
              ? Number(value)
              : field === 'activo'
                ? Boolean(value)
                : value,
          } as DashboardCatalogDuration
        : duration),
      precio: field === 'precio' && current.duraciones[index]?.duracion_meses === 1 ? Number(value) : current.precio,
    }));
  }

  editAdmin(admin: DashboardAdmin): void {
    this.activeSection.set('admins');
    this.editingAdminId.set(admin.id);
    this.adminForm.set({
      name: admin.name,
      email: admin.email,
      password: '',
    });
  }

  saveAdmin(): void {
    if (this.savingAdmin()) return;

    const request = this.editingAdminId()
      ? this.api.updateAdmin(this.editingAdminId() as number, this.adminForm())
      : this.api.createAdmin(this.adminForm());

    this.savingAdmin.set(true);
    this.message.set('');
    this.error.set('');

    request.subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.message.set(this.editingAdminId() ? 'Admin actualizado.' : 'Admin creado.');
        this.cancelAdminEdit();
        this.savingAdmin.set(false);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudo guardar el admin.');
        this.savingAdmin.set(false);
      },
    });
  }

  deleteAdmin(admin: DashboardAdmin): void {
    this.confirmDanger('Eliminar admin', `Se eliminara ${admin.email}.`).then((confirmed) => {
      if (!confirmed) return;

      this.api.deleteAdmin(admin.id).subscribe({
        next: (response) => {
          this.data.set(response.data);
          this.success('Admin eliminado.');
        },
        error: (error) => this.fail(error?.error?.message || 'No se pudo eliminar el admin.'),
      });
    });
  }

  cancelAdminEdit(): void {
    this.editingAdminId.set(null);
    this.adminForm.set(this.emptyAdmin());
  }

  updateAdminField<K extends keyof DashboardAdminPayload>(field: K, value: DashboardAdminPayload[K]): void {
    this.adminForm.update((current) => ({ ...current, [field]: value }));
  }

  savePayments(): void {
    if (this.savingPayments()) return;

    this.savingPayments.set(true);
    this.message.set('');
    this.error.set('');

    this.api.updatePaymentSettings(this.paymentForm()).subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillPaymentForm();
        this.success('QR, metodos de pago y vendedor actualizados.');
        this.savingPayments.set(false);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.fail(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudo guardar pagos.');
        this.savingPayments.set(false);
      },
    });
  }

  updatePaymentSellerField<K extends keyof DashboardPaymentSettings['seller']>(field: K, value: DashboardPaymentSettings['seller'][K]): void {
    this.paymentForm.update((current) => ({
      ...current,
      seller: {
        ...current.seller,
        [field]: value,
      },
    }));
  }

  updatePaymentInstructions(value: string): void {
    this.paymentForm.update((current) => ({ ...current, instructions: value }));
  }

  updatePaymentField<K extends keyof DashboardPaymentMethod>(index: number, field: K, value: DashboardPaymentMethod[K]): void {
    this.paymentForm.update((current) => ({
      ...current,
      methods: current.methods.map((method, currentIndex) => (
        currentIndex === index ? { ...method, [field]: value } : method
      )),
    }));
  }

  uploadPaymentQr(method: DashboardPaymentMethod, event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';

    if (!file || this.uploadingPaymentQr()) return;

    this.uploadingPaymentQr.set(method.id);
    this.message.set('');
    this.error.set('');

    this.api.uploadPaymentQr(method.id, file).subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillPaymentForm();
        this.success('QR subido correctamente.');
        this.uploadingPaymentQr.set(null);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.fail(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudo subir el QR.');
        this.uploadingPaymentQr.set(null);
      },
    });
  }

  clearImportedData(): void {
    if (this.clearingData() || this.clearConfirmation() !== 'LIMPIAR') return;

    this.confirmDanger('Limpiar cuentas importadas', 'Esto eliminara cuentas y perfiles importados desde Excel.').then((confirmed) => {
      if (!confirmed) return;

      this.clearingData.set(true);
      this.message.set('');
      this.error.set('');

      this.api.clearImportedData(this.clearConfirmation()).subscribe({
        next: (response) => {
          this.data.set(response.data);
          this.clearConfirmation.set('');
          this.success('Cuentas y perfiles importados eliminados.');
          this.clearingData.set(false);
        },
        error: (error) => {
          this.fail(error?.error?.message || 'No se pudo limpiar la data.');
          this.clearingData.set(false);
        },
      });
    });
  }

  deleteTutorialMedia(key: string): void {
    this.confirmDanger('Eliminar archivo', 'Se eliminara la imagen o video de este tutorial.').then((confirmed) => {
      if (!confirmed) return;

      this.api.deleteTutorialMedia(key).subscribe({
        next: (response) => {
          this.data.set(response.data);
          this.fillTutorialForm();
          this.success('Archivo del tutorial eliminado.');
        },
        error: (error) => this.fail(error?.error?.message || 'No se pudo eliminar el archivo.'),
      });
    });
  }

  selectPlatform(platform: string): void {
    this.selectedPlatform.set(platform);
  }

  usePlatform(plataforma: string): void {
    this.activeSection.set('excel');
    this.rangeForm.update((current) => ({
      ...current,
      plataforma,
      producto_slug: current.producto_slug || this.slug(plataforma),
    }));
  }

  editRange(range: DashboardRange): void {
    this.activeSection.set('excel');
    this.editingRangeId.set(range.id);
    this.rangeForm.set({
      plataforma: range.plataforma,
      nombre_tabla: range.nombre_tabla || '',
      producto_slug: range.producto_slug || '',
      archivo_url: range.archivo_url || '',
      bot_codigo_url: range.bot_codigo_url || '',
      bot_soporte_url: range.bot_soporte_url || '',
      hoja_excel: range.hoja_excel,
      fila_inicio: range.fila_inicio,
      fila_fin: range.fila_fin,
      columna_perfil: range.columna_perfil || 'F',
      columna_pin: range.columna_pin || 'G',
      columna_numero: range.columna_numero || 'H',
      columna_vendedor_igarlos: range.columna_vendedor_igarlos || 'I',
      columna_vendedor_nikol: range.columna_vendedor_nikol || 'J',
      columna_costo: range.columna_costo || 'K',
      columna_fecha_inicio: range.columna_fecha_inicio || 'L',
      columna_fecha_fin: range.columna_fecha_fin || 'M',
      columna_estado: range.columna_estado || 'N',
      columna_correo: range.columna_correo || 'U',
      columna_password: range.columna_password || 'V',
      columna_cliente_acceso_usuario: range.columna_cliente_acceso_usuario || 'X',
      activo: range.activo,
    });
  }

  updateTutorialMedia(key: string, event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    this.tutorialForm.update((current) => ({
      ...current,
      [key]: {
        ...(current[key] ?? { title: '', steps: '', media: null }),
        media: file,
      },
    }));
  }

  saveTutorial(key: string): void {
    if (this.savingTutorialKey()) return;

    const payload = this.tutorialForm()[key];
    if (!payload) return;

    this.savingTutorialKey.set(key);
    this.message.set('');
    this.error.set('');

    this.api.updateTutorial(key, payload).subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillTutorialForm();
        this.message.set('Tutorial actualizado.');
        this.savingTutorialKey.set(null);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudo guardar el tutorial.');
        this.savingTutorialKey.set(null);
      },
    });
  }

  saveRange(): void {
    if (this.savingRange()) return;

    const payload = this.rangeForm();
    const request = this.editingRangeId()
      ? this.api.updateRange(this.editingRangeId() as number, payload)
      : this.api.createRange(payload);

    this.savingRange.set(true);
    this.message.set('');
    this.error.set('');

    request.subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.message.set(this.editingRangeId() ? 'Tabla actualizada.' : 'Tabla creada.');
        this.cancelRangeEdit();
        this.savingRange.set(false);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudo guardar la tabla.');
        this.savingRange.set(false);
      },
    });
  }

  deleteRange(range: DashboardRange): void {
    this.confirmDanger('Eliminar tabla Excel', `Se eliminara ${range.nombre_tabla || range.plataforma}.`).then((confirmed) => {
      if (!confirmed) return;

      this.api.deleteRange(range.id).subscribe({
        next: (response) => {
          this.data.set(response.data);
          this.success('Tabla eliminada.');
        },
        error: (error) => this.fail(error?.error?.message || 'No se pudo eliminar la tabla.'),
      });
    });
  }

  cancelRangeEdit(): void {
    this.editingRangeId.set(null);
    this.rangeForm.set(this.emptyRange());
  }

  updateRangeField<K extends keyof DashboardRangePayload>(field: K, value: DashboardRangePayload[K]): void {
    this.rangeForm.update((current) => ({ ...current, [field]: value }));
  }

  editAccountBots(account: DashboardAccount): void {
    this.editingAccountId.set(account.id);
    this.accountBotForm.set({
      cliente_acceso_usuario: account.cliente_acceso_usuario || '',
      bot_preferencia: this.isNetflixAccount(account) ? account.bot_preferencia || 'principal' : 'principal',
      bot_hogar_url: account.bot_hogar_url || '',
      bot_temporal_url: account.bot_temporal_url || '',
      bot_acceso4_url: account.bot_acceso4_url || '',
    });
  }

  saveAccountBots(): void {
    const accountId = this.editingAccountId();
    if (!accountId || this.savingAccountBots()) return;

    this.savingAccountBots.set(true);
    this.message.set('');
    this.error.set('');

    const account = this.data()?.accounts.find((item) => item.id === accountId);
    const form = this.accountBotForm();
    const payload: AccountBotLinksPayload = {
      ...form,
      bot_preferencia: account && this.isNetflixAccount(account) ? form.bot_preferencia : 'principal',
      bot_hogar_url: '',
      bot_temporal_url: '',
      bot_acceso4_url: account && this.isNetflixAccount(account) && form.bot_preferencia === 'personalizado' ? form.bot_acceso4_url : '',
    };

    this.api.updateAccountBotLinks(accountId, payload).subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.message.set('Links de bot de la cuenta actualizados.');
        this.cancelAccountBotEdit();
        this.savingAccountBots.set(false);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudieron guardar los links.');
        this.savingAccountBots.set(false);
      },
    });
  }

  cancelAccountBotEdit(): void {
    this.editingAccountId.set(null);
    this.accountBotForm.set(this.emptyAccountBotLinks());
  }

  updateAccountBotField<K extends keyof AccountBotLinksPayload>(field: K, value: AccountBotLinksPayload[K]): void {
    this.accountBotForm.update((current) => ({ ...current, [field]: value }));
  }

  isNetflixAccount(account: DashboardAccount): boolean {
    return `${account.source_platforma || ''}`.toLowerCase().includes('netflix');
  }

  runImap(): void {
    if (this.runningImap()) return;

    this.runningImap.set(true);
    this.message.set('');
    this.error.set('');

    this.api.runImap().subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillImapForm();
        this.imapOutput.set(response.output || '');
        this.message.set(response.message || 'IMAP ejecutado.');
        this.runningImap.set(false);
      },
      error: (error) => {
        this.error.set(error?.error?.message || 'No se pudo ejecutar IMAP.');
        this.runningImap.set(false);
      },
    });
  }

  testImap(): void {
    if (this.testingImap()) return;

    this.testingImap.set(true);
    this.message.set('');
    this.error.set('');
    this.imapOutput.set('');
    this.imapTestResult.set(null);

    this.api.testImap().subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillImapForm();
        this.imapTestResult.set({
          criteria: response.criteria,
          found: response.found,
          latest: response.latest,
        });
        this.message.set(`${response.message} Encontrados: ${response.found}.`);
        this.testingImap.set(false);
      },
      error: (error) => {
        this.error.set(error?.error?.detail || error?.error?.message || 'No se pudo probar IMAP.');
        this.testingImap.set(false);
      },
    });
  }

  copyCronUrl(): void {
    const url = this.imap()?.cron_url;
    if (!url) return;

    navigator.clipboard
      ?.writeText(url)
      .then(() => this.success('URL del cron copiada.'))
      .catch(() => window.prompt('URL del cron', url));
  }

  copyText(value: string, label = 'Valor'): void {
    if (!value) return;

    navigator.clipboard
      ?.writeText(value)
      .then(() => this.success(`${label} copiado.`))
      .catch(() => window.prompt(label, value));
  }

  clearDashboardOnlyEmails(): void {
    if (this.clearingDashboardOnlyEmails()) return;

    this.confirmDanger(
      'Limpiar solo dashboard',
      'Se eliminaran los correos que ya no son visibles para el cliente y solo quedan como historial en dashboard.'
    ).then((confirmed) => {
      if (!confirmed) return;

      this.clearingDashboardOnlyEmails.set(true);
      this.api.clearImapDashboardOnlyHistory().subscribe({
        next: (response) => {
          this.data.set(response.data);
          this.success(response.message || 'Historial solo-dashboard limpiado.');
          this.clearingDashboardOnlyEmails.set(false);
        },
        error: (error) => {
          this.fail(error?.error?.message || 'No se pudo limpiar el historial solo-dashboard.');
          this.clearingDashboardOnlyEmails.set(false);
        },
      });
    });
  }

  deleteImapHistoryItem(item: DashboardImapItem): void {
    this.confirmDanger(
      'Eliminar correo del historial',
      `Se eliminara el correo ${item.asunto || `#${item.id}`} del dashboard.`
    ).then((confirmed) => {
      if (!confirmed) return;

      this.api.deleteImapHistoryItem(item.id).subscribe({
        next: (response) => {
          this.data.set(response.data);
          this.success(response.message || 'Correo eliminado del historial.');
        },
        error: (error) => {
          this.fail(error?.error?.message || 'No se pudo eliminar el correo.');
        },
      });
    });
  }

  saveImapSettings(): void {
    if (this.savingImap()) return;

    this.savingImap.set(true);
    this.message.set('');
    this.error.set('');

    this.api.updateImapSettings(this.imapForm()).subscribe({
      next: (response) => {
        this.data.set(response.data);
        this.fillImapForm();
        this.message.set('Configuracion IMAP/Cron guardada.');
        this.savingImap.set(false);
      },
      error: (error) => {
        const errors = error?.error?.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : error?.error?.message || 'No se pudo guardar IMAP/Cron.');
        this.savingImap.set(false);
      },
    });
  }

  updateImapField<K extends keyof DashboardImapSettingsPayload>(field: K, value: DashboardImapSettingsPayload[K]): void {
    this.imapForm.update((current) => ({ ...current, [field]: value }));
  }

  logout(): void {
    Swal.fire({
      title: 'Cerrar sesion',
      text: 'Vas a salir del dashboard.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Si, salir',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ef4444',
      background: '#0b1020',
      color: '#f8fbff',
    }).then((result) => {
      if (!result.isConfirmed) return;

      this.api.logout().subscribe({
        next: () => {
          window.location.href = '/login';
        },
        error: () => {
          window.location.href = '/login';
        },
      });
    });
  }

  money(value: string | number | null): string {
    return value === null || value === '' ? '-' : `S/ ${value}`;
  }

  profileState(profile: DashboardProfile): string {
    return profile.estado_excel || (profile.ocupado ? 'Activo' : 'Disponible');
  }

  openUrl(url: string | null): void {
    if (!url) return;
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  openImapItemLink(item: DashboardImapItem): void {
    const url = item.action_url || null;
    if (!url) return;

    this.openUrl(url);
  }

  durationLabel(seconds: number): string {
    const safeSeconds = Math.max(0, Math.floor(seconds));
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const remainder = safeSeconds % 60;

    if (hours > 0) {
      return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
    }

    return `${minutes.toString().padStart(2, '0')}:${remainder.toString().padStart(2, '0')}`;
  }

  imapValueLabel(item: DashboardImapItem): string {
    if (item.codigo) return `Codigo: ${item.codigo}`;
    if (item.action_url) return 'Link extraido';
    if (item.valor_extraido) return item.valor_extraido;
    if (item.found_links.length > 0) return `${item.found_links.length} link(s) detectado(s)`;

    return 'Sin extraccion valida';
  }

  showImapOriginal(item: DashboardImapItem): void {
    void Swal.fire({
      title: item.asunto || 'Correo original',
      html: `
        <div style="text-align:left;display:grid;gap:12px">
          <div><b>Destino:</b> ${this.escapeHtml(item.destinatario_original || '-')}</div>
          <div><b>Remitente:</b> ${this.escapeHtml(item.remitente || '-')}</div>
          <div><b>Message-ID:</b> ${this.escapeHtml(item.message_id || '-')}</div>
          <pre style="white-space:pre-wrap;max-height:50vh;overflow:auto;background:#020617;padding:12px;border-radius:12px;">${this.escapeHtml(item.raw_email || 'Sin raw_email guardado.')}</pre>
        </div>
      `,
      width: 900,
      confirmButtonColor: '#27e0ff',
      background: '#0b1020',
      color: '#f8fbff',
    });
  }

  showImapBodies(item: DashboardImapItem): void {
    void Swal.fire({
      title: 'HTML / texto original',
      html: `
        <div style="text-align:left;display:grid;gap:16px">
          <div>
            <b>Texto original</b>
            <pre style="white-space:pre-wrap;max-height:26vh;overflow:auto;background:#020617;padding:12px;border-radius:12px;">${this.escapeHtml(item.text_body_original || 'Sin texto plano guardado.')}</pre>
          </div>
          <div>
            <b>HTML original</b>
            <pre style="white-space:pre-wrap;max-height:26vh;overflow:auto;background:#020617;padding:12px;border-radius:12px;">${this.escapeHtml(item.html_body_original || 'Sin HTML guardado.')}</pre>
          </div>
        </div>
      `,
      width: 980,
      confirmButtonColor: '#27e0ff',
      background: '#0b1020',
      color: '#f8fbff',
    });
  }

  showImapLinks(item: DashboardImapItem): void {
    const links = item.found_links || [];

    void Swal.fire({
      title: 'Links encontrados',
      html: links.length > 0
        ? `<div style="text-align:left;display:grid;gap:10px">${links
            .map((link, index) => `<div><b>${index + 1}.</b> <a href="${this.escapeHtml(link)}" target="_blank" rel="noopener noreferrer">${this.escapeHtml(link)}</a></div>`)
            .join('')}</div>`
        : '<p>No se detectaron links en este correo.</p>',
      width: 980,
      confirmButtonColor: '#27e0ff',
      background: '#0b1020',
      color: '#f8fbff',
    });
  }

  private slug(value: string): string {
    return value
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
  }

  private async confirmDanger(title: string, text: string): Promise<boolean> {
    const result = await Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Si, continuar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#334155',
      background: '#0b1020',
      color: '#f8fbff',
    });

    return result.isConfirmed;
  }

  private success(message: string): void {
    this.message.set(message);
    this.error.set('');
    void Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: message,
      showConfirmButton: false,
      timer: 1800,
      timerProgressBar: true,
      background: '#0b1020',
      color: '#f8fbff',
    });
  }

  private fail(message: string): void {
    this.error.set(message);
    this.message.set('');
    void Swal.fire({
      icon: 'error',
      title: 'No se pudo completar',
      text: message,
      confirmButtonColor: '#27e0ff',
      background: '#0b1020',
      color: '#f8fbff',
    });
  }

  private escapeHtml(value: string): string {
    return value
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }

  private emptyRange(): DashboardRangePayload {
    return {
      plataforma: 'Netflix',
      nombre_tabla: '',
      producto_slug: '',
      archivo_url: '',
      bot_codigo_url: '',
      bot_soporte_url: '',
      hoja_excel: 'NETFLIX',
      fila_inicio: 3,
      fila_fin: 77,
      columna_perfil: 'F',
      columna_pin: 'G',
      columna_numero: 'H',
      columna_vendedor_igarlos: 'I',
      columna_vendedor_nikol: 'J',
      columna_costo: 'K',
      columna_fecha_inicio: 'L',
      columna_fecha_fin: 'M',
      columna_estado: 'N',
      columna_correo: 'U',
      columna_password: 'V',
      columna_cliente_acceso_usuario: 'X',
      activo: true,
    };
  }

  private emptyCatalog(): DashboardCatalogPayload {
    return {
      nombre: '',
      imagen: '',
      precio: 0,
      descripcion: '',
      features: '',
      activacion: '',
      terminos: '',
      activo: true,
      duraciones: this.normalizeCatalogDurations([], 0),
    };
  }

  private normalizeCatalogDurations(durations: DashboardCatalogDuration[], fallbackPrice: number): DashboardCatalogDuration[] {
    const map = new Map((durations || []).map((duration) => [duration.duracion_meses, duration]));

    return [1, 2, 3, 6].map((months) => {
      const existing = map.get(months as 1 | 2 | 3 | 6);

      return {
        id: existing?.id ?? null,
        duracion_meses: months as 1 | 2 | 3 | 6,
        precio: Number(existing?.precio ?? (months === 1 ? fallbackPrice : 0)),
        activo: existing?.activo ?? (months === 1),
      };
    });
  }

  private emptyAdmin(): DashboardAdminPayload {
    return {
      name: '',
      email: '',
      password: '',
    };
  }

  private emptyPaymentSettings(): DashboardPaymentSettings {
    return {
      seller: {
        business_name: 'IG UNIVERSE',
        display_name: 'IG UNIVERSE',
        contact_name: 'Igarlos R Mamani Q',
        whatsapp: '51954850003',
        phone: '954850003',
        email: '',
        address: '',
        support_text: 'Finaliza tu compra y envia el comprobante.',
      },
      instructions: '1) Escanea QR o transfiere al numero - 2) Paga el monto exacto - 3) Envia el comprobante por WhatsApp - 4) Te activamos rapido.',
      methods: [
        {
          id: 1,
          title: 'Yape / Plin',
          subtitle: 'Opcion 1',
          badge: 'recomendado',
          recommended: true,
          qr_src: '/images/qr-yape.jpeg',
          qr_fallback: 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:954850003&color=4A6FFF&bgcolor=ffffff',
          account_name: 'Igarlos R Mamani Q',
          account_phone: '954850003',
          copy_phone: '907978279',
          whatsapp: '51954850003',
          active: true,
        },
        {
          id: 2,
          title: 'Yape / Plin',
          subtitle: 'Opcion 2',
          badge: '',
          recommended: false,
          qr_src: '/images/qr-yape-2.jpeg',
          qr_fallback: 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:968238516&color=4A6FFF&bgcolor=ffffff',
          account_name: 'Jennifer N Gallegos Q',
          account_phone: '968238516',
          copy_phone: '968238516',
          whatsapp: '51968238516',
          active: true,
        },
      ],
    };
  }

  private emptyAccountBotLinks(): AccountBotLinksPayload {
    return {
      cliente_acceso_usuario: '',
      bot_preferencia: 'principal',
      bot_hogar_url: '',
      bot_temporal_url: '',
      bot_acceso4_url: '',
    };
  }

  private emptyImapSettings(): DashboardImapSettingsPayload {
    return {
      imap_server: '',
      imap_user: '',
      imap_password: '',
      imap_search_criteria: 'UNSEEN',
      imap_mark_seen: true,
      emails_max_minutes: 7,
      cron_token: '',
    };
  }

  private fillImapForm(): void {
    const imap = this.imap();
    if (!imap) return;

    this.imapForm.set({
      imap_server: imap.mailbox_raw || '',
      imap_user: imap.username || '',
      imap_password: '',
      imap_search_criteria: imap.search_criteria || 'UNSEEN',
      imap_mark_seen: imap.mark_seen,
      emails_max_minutes: imap.retention_minutes || 7,
      cron_token: '',
    });
  }

  private fillTutorialForm(): void {
    const forms: Record<string, { title: string; steps: string; media: File | null }> = {};

    for (const [key, tutorial] of Object.entries(this.tutorials()) as Array<[string, DashboardTutorial]>) {
      forms[key] = {
        title: tutorial.title || this.tutorialLabels()[key] || key,
        steps: (tutorial.steps ?? []).join('\n'),
        media: null,
      };
    }

    this.tutorialForm.set(forms);
  }

  private fillPaymentForm(): void {
    this.paymentForm.set(JSON.parse(JSON.stringify(this.paymentSettings())) as DashboardPaymentSettings);
  }
}
