import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, Subject } from 'rxjs';
import Swal from 'sweetalert2';

import { NetcodeAccessPage } from './netcode-access-page';
import { NetcodeApi } from './netcode-api';

describe('NetcodeAccessPage', () => {
  let fixture: ComponentFixture<NetcodeAccessPage>;
  let component: NetcodeAccessPage;
  let api: {
    tutorials: ReturnType<typeof vi.fn>;
    searchEmail: ReturnType<typeof vi.fn>;
    validateAccess: ReturnType<typeof vi.fn>;
  };

  beforeEach(async () => {
    vi.useFakeTimers();
    vi.spyOn(Swal, 'fire').mockResolvedValue({ isConfirmed: false } as never);
    vi.spyOn(Swal, 'showLoading').mockImplementation(() => undefined);

    api = {
      tutorials: vi.fn(() => of({})),
      searchEmail: vi.fn(() => of({ status: 'not_found', message: 'No encontrado' })),
      validateAccess: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [NetcodeAccessPage],
      providers: [{ provide: NetcodeApi, useValue: api }],
    }).compileComponents();

    fixture = TestBed.createComponent(NetcodeAccessPage);
    component = fixture.componentInstance;
    component.account.set({
      status: 'success',
      step: 'pin',
      message: 'OK',
      cuenta: {
        id: 25,
        email: 'cliente@example.com',
        password: null,
        producto: 'Netflix',
        perfiles_total: 5,
        perfiles_usados: 1,
        activo: true,
        hoja_excel: 'NETFLIX',
        fila_excel: 3,
        cliente_acceso_usuario: 'cliente-demo',
        bot_preferencia: 'principal',
        bot_hogar_url: null,
        bot_temporal_url: null,
        bot_acceso4_url: null,
        bot_acceso4_masked_url: null,
      },
    });
  });

  afterEach(() => {
    component.cancelSearch();
    vi.runOnlyPendingTimers();
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it('shows a found access code and stops polling immediately', async () => {
    api.searchEmail.mockReturnValue(
      of({
        status: 'success',
        found: true,
        message: 'OK',
        value: '1234',
        type: 'codigo',
        email: 'cliente@example.com',
        received_at: '2026-08-16 05:20:03',
        processed_at: '2026-08-16 05:20:03',
        expires_at: '2026-08-16 05:27:03',
        seconds_remaining: 420,
        validity_source: 'processed_at',
      })
    );

    await component.startAccessCodeSearch(false);

    expect(component.resultValue()).toBe('1234');
    expect(component.resultType()).toBe('codigo');
    expect(component.viewState()).toBe('result');
    expect(component.isSearching()).toBe(false);
    expect((component as unknown as { pollingTimer: number | null }).pollingTimer).toBeNull();
    expect((component as unknown as { pollingRequest: unknown | null }).pollingRequest).toBeNull();
    expect(api.searchEmail).toHaveBeenCalledWith({ account_id: 25, subject: 'acceso4' });
  });

  it('formats the countdown as MM:SS and stops at 00:00', () => {
    (component as unknown as { startResultValidityCountdown: (seconds: number) => void }).startResultValidityCountdown(65);

    expect(component.resultValidityLabel()).toBe('01:05');

    vi.advanceTimersByTime(65000);

    expect(component.resultValidityLabel()).toBe('00:00');
    expect(component.resultSecondsLeft()).toBe(0);
  });

  it('shows retry after the first search without result', async () => {
    await component.startAccessCodeSearch(false);

    component.timeLeft.set(0);
    (component as unknown as { tick: () => void }).tick();

    expect(component.searchAttempt()).toBe(1);
    expect(component.canRetrySearch()).toBe(true);
    expect(component.isSearching()).toBe(false);
  });

  it('allows only two searches and does not start a third one', async () => {
    await component.startAccessCodeSearch(false);
    component.timeLeft.set(0);
    (component as unknown as { tick: () => void }).tick();

    component.retrySearch();
    component.timeLeft.set(0);
    (component as unknown as { tick: () => void }).tick();

    expect(component.searchAttempt()).toBe(2);
    expect(component.canRetrySearch()).toBe(false);

    await component.startAccessCodeSearch(false);

    expect(api.searchEmail).toHaveBeenCalledTimes(2);
  });

  it('does not create simultaneous polling timers or overlapping requests', async () => {
    const pendingRequest = new Subject<unknown>();
    api.searchEmail.mockReturnValue(pendingRequest.asObservable());

    await component.startAccessCodeSearch(false);

    expect(api.searchEmail).toHaveBeenCalledTimes(1);
    expect((component as unknown as { pollingTimer: number | null }).pollingTimer).toBeNull();

    vi.advanceTimersByTime(20000);

    expect(api.searchEmail).toHaveBeenCalledTimes(1);

    pendingRequest.next({ status: 'not_found', message: 'Nada' });
    pendingRequest.complete();

    vi.advanceTimersByTime(4000);

    expect(api.searchEmail).toHaveBeenCalledTimes(2);
  });
});
