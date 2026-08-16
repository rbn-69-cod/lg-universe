import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import Swal from 'sweetalert2';

import { NetcodeAccessPage } from './netcode-access-page';
import { NetcodeApi } from './netcode-api';

describe('NetcodeAccessPage', () => {
  let fixture: ComponentFixture<NetcodeAccessPage>;
  let component: NetcodeAccessPage;
  let api: any;

  beforeEach(async () => {
    vi.spyOn(Swal, 'fire').mockResolvedValue({ isConfirmed: false } as any);
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
        email: 'cliente@example.com',
        password: null,
        producto: 'Netflix',
        perfiles_total: 5,
        perfiles_usados: 1,
        activo: true,
        hoja_excel: 'NETFLIX',
        fila_excel: 3,
      },
    });
  });

  afterEach(() => {
    component.cancelSearch();
    vi.restoreAllMocks();
  });

  it('shows a found access code and stops polling immediately', async () => {
    api.searchEmail.mockReturnValue(of({ status: 'success', message: 'OK', valor_extraido: '1234', tipo: 'codigo' }));

    await component.startAccessCodeSearch(false);

    expect(component.resultValue()).toBe('1234');
    expect(component.viewState()).toBe('result');
    expect(component.isSearching()).toBe(false);
    expect((component as unknown as { polling: number | null }).polling).toBeNull();
  });

  it('allows only two searches and does not start a third one', async () => {
    await component.startAccessCodeSearch(false);
    component.timeLeft.set(0);
    (component as unknown as { tick: () => void }).tick();

    expect(component.searchAttempt()).toBe(1);
    expect(component.canRetrySearch()).toBe(true);

    component.retrySearch();
    component.timeLeft.set(0);
    (component as unknown as { tick: () => void }).tick();

    expect(component.searchAttempt()).toBe(2);
    expect(component.canRetrySearch()).toBe(false);

    await component.startAccessCodeSearch(false);

    expect(api.searchEmail).toHaveBeenCalledTimes(2);
  });
});
