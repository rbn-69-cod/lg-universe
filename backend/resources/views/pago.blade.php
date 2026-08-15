{{-- resources/views/pago.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>IG UNIVERSE • Pago</title>

  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    /* ===== TODO TU CSS EXISTENTE SE MANTIENE IGUAL ===== */
    :root{
      --bg:#070814;
      --purple:#6128ff;
      --blue:#1aa9ff;
      --cyan:#22e6ff;
      --pink:#ff2bd6;

      --text:#fff;
      --muted: rgba(255,255,255,.78);
      --stroke: rgba(255,255,255,.14);

      --shadowGlow: 0 20px 60px rgba(0,0,0,.55), 0 0 40px rgba(255,43,214,.18), 0 0 50px rgba(34,230,255,.12);
      --shadowSoft: 0 12px 30px rgba(0,0,0,.30);

      --radiusXL: 30px;
      --radiusL: 22px;
      --radiusM: 16px;

      --gradBrand: linear-gradient(135deg, rgba(97,40,255,.95), rgba(26,169,255,.90));
      --gradHot: linear-gradient(135deg, rgba(255,43,214,.95), rgba(97,40,255,.92));
      --gradEdge: linear-gradient(90deg, rgba(255,43,214,.85), rgba(34,230,255,.85));

      --success: #00ff9d;
      --warn: #ffb020;

      --easePop: cubic-bezier(.2,.9,.25,1);
    }

    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:"Exo 2", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background:
        radial-gradient(1200px 800px at 15% 10%, rgba(255,50,60,.22), transparent 55%),
        radial-gradient(900px 700px at 85% 70%, rgba(0,180,255,.28), transparent 55%),
        radial-gradient(600px 600px at 60% 20%, rgba(158,90,255,.22), transparent 60%),
        var(--bg);
      color:var(--text);
      min-height:100vh;
      overflow-x:hidden;
      padding-bottom: 40px;
    }

    .stars{
      position:fixed; inset:0; pointer-events:none; z-index:0;
      background:
        radial-gradient(1px 1px at 15% 20%, rgba(255,255,255,.7), transparent 40%),
        radial-gradient(1px 1px at 60% 35%, rgba(255,255,255,.55), transparent 40%),
        radial-gradient(1px 1px at 85% 15%, rgba(255,255,255,.6), transparent 40%),
        radial-gradient(1px 1px at 30% 70%, rgba(255,255,255,.5), transparent 40%),
        radial-gradient(1px 1px at 78% 80%, rgba(255,255,255,.5), transparent 40%),
        radial-gradient(1px 1px at 45% 90%, rgba(255,255,255,.45), transparent 40%);
      opacity:.55;
      filter: drop-shadow(0 0 6px rgba(34,230,255,.10));
      animation: drift 18s linear infinite;
    }
    @keyframes drift{
      0%{transform:translate3d(0,0,0)}
      50%{transform:translate3d(-10px, 12px,0)}
      100%{transform:translate3d(0,0,0)}
    }
    .scanlines{
      position:fixed;inset:0;pointer-events:none;z-index:1;
      background:linear-gradient(to bottom,transparent 50%,rgba(34,230,255,.06) 50%);
      background-size:100% 4px;
      opacity:.20;
      animation: scanShift 8s linear infinite;
    }
    @keyframes scanShift{
      0%{transform:translateY(0)}
      100%{transform:translateY(10px)}
    }
    .glow{
      position:fixed;inset:-20%;pointer-events:none;z-index:0;
      background:radial-gradient(circle at center,rgba(255,43,214,.10) 0%,rgba(34,230,255,.06) 30%,transparent 70%);
      opacity:.85;
      animation: breathe 6s ease-in-out infinite;
    }
    @keyframes breathe{
      0%,100%{transform:scale(1)}
      50%{transform:scale(1.03)}
    }

    .wrap{width:min(1100px,94vw);margin:0 auto;position:relative;z-index:2}

    /* Header */
    .header{
      position:sticky; top:0; z-index:20;
      padding:16px 14px 12px;
      background: linear-gradient(180deg, rgba(7,8,20,.92), rgba(7,8,20,.55));
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255,255,255,.10);
    }
    .headerInner{
      width:min(1100px,94vw);
      margin:0 auto;
      display:flex; align-items:center; justify-content:space-between; gap:14px;
    }
    .brandLeft{display:flex;align-items:center;gap:12px;min-width:0}
    .brandMark{
      width:44px;height:44px;border-radius:14px;
      background: linear-gradient(135deg, rgba(255,43,214,.25), rgba(34,230,255,.18));
      border:1px solid rgba(255,255,255,.14);
      box-shadow: var(--shadowSoft);
      position:relative; overflow:hidden;
    }
    .brandMark:before{
      content:"";position:absolute;inset:-2px;
      background: conic-gradient(from 180deg, rgba(255,43,214,.0), rgba(255,43,214,.55), rgba(34,230,255,.55), rgba(255,43,214,.0));
      animation: spin 3.6s linear infinite;
      opacity:.55; filter: blur(8px);
    }
    @keyframes spin{to{transform:rotate(360deg)}}

    .logo{
      font-family:"Orbitron";
      font-size:18px;
      font-weight:900;
      letter-spacing:1px;
      text-transform:uppercase;
      line-height:1.1;
      white-space:nowrap;
    }
    .logo span{
      background:linear-gradient(90deg,#fff,rgba(255,255,255,.85),rgba(34,230,255,.85));
      -webkit-background-clip:text;background-clip:text;color:transparent;
      filter:drop-shadow(0 0 18px rgba(255,43,214,.25));
    }
    .subtitle{
      margin-top:2px;
      color:rgba(255,255,255,.70);
      font-size:12px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      max-width:54vw;
    }
    .backBtn{
      border:none;cursor:pointer;border-radius:999px;
      padding:10px 12px;
      background:rgba(0,0,0,.16);
      border:1px solid rgba(255,255,255,.14);
      color:#fff;
      display:flex;align-items:center;gap:10px;
      font-family:"Orbitron";font-weight:900;letter-spacing:1px;font-size:12px;
      box-shadow: var(--shadowSoft);
      transition: transform .18s var(--easePop), filter .18s var(--easePop);
      text-decoration:none;
    }
    .backBtn:hover{transform:translateY(-2px);filter:brightness(1.06)}

    /* Layout */
    .grid{
      display:grid;
      grid-template-columns: 1fr 1.2fr;
      gap:16px;
      margin:18px 0 0;
      align-items:start;
    }
    @media (max-width: 980px){
      .grid{grid-template-columns:1fr}
    }

    .panel{
      border-radius: var(--radiusXL);
      border:1px solid rgba(255,255,255,.14);
      background:rgba(0,0,0,.16);
      backdrop-filter: blur(12px);
      box-shadow: var(--shadowGlow);
      overflow:hidden;
      position:relative;
      transform: translateY(16px) scale(.985);
      opacity: 0;
      transition: transform .45s var(--easePop), opacity .45s var(--easePop);
    }
    .panel.is-in{transform: translateY(0) scale(1); opacity:1}
    .panel:before{
      content:"";position:absolute;inset:-2px;
      background:
        radial-gradient(520px 220px at 20% 0%,rgba(255,43,214,.16),transparent 65%),
        radial-gradient(520px 260px at 90% 100%,rgba(34,230,255,.12),transparent 65%);
      pointer-events:none;opacity:.95;
    }
    .panelInner{position:relative;z-index:1;padding:16px}

    .panelTitle{
      display:flex;align-items:center;justify-content:space-between;gap:10px;
      padding:14px 16px;
      border-bottom:1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.10);
    }
    .panelTitle h2{
      font-family:"Orbitron";
      font-size:13px;
      letter-spacing:1px;
      text-transform:uppercase;
      display:flex;align-items:center;gap:10px;
    }
    .status{
      font-family:"Orbitron";
      font-size:10px;
      letter-spacing:1px;
      padding:7px 10px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.18);
      color: rgba(255,255,255,.85);
      display:flex;align-items:center;gap:8px;
      white-space:nowrap;
    }
    .status i{color: var(--warn); filter: drop-shadow(0 0 10px rgba(255,176,32,.25));}

    /* Cart summary list */
    .list{padding:14px 16px;max-height:52vh;overflow:auto}
    .list::-webkit-scrollbar{width:8px}
    .list::-webkit-scrollbar-thumb{background:linear-gradient(180deg,rgba(255,43,214,.75),rgba(34,230,255,.75));border-radius:999px}

    .line{
      border:1px solid rgba(255,255,255,.10);
      background:rgba(255,255,255,.06);
      border-radius:18px;padding:12px;margin-bottom:10px;
      transition: transform .18s var(--easePop), border-color .18s var(--easePop);
    }
    .line:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.18)}
    .lineTop{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .lineName{font-weight:900;font-size:14px;line-height:1.2}
    .lineMeta{margin-top:6px;color:rgba(255,255,255,.62);font-size:12px}
    .lineAmt{font-family:"Orbitron";font-weight:900;font-size:12px;color:rgba(34,230,255,.95);white-space:nowrap}
    .lineTotal{margin-top:10px;display:flex;justify-content:space-between;align-items:center}
    .pill{
      display:inline-flex;align-items:center;gap:8px;
      font-family:"Orbitron";font-weight:900;font-size:10px;letter-spacing:1px;
      padding:7px 10px;border-radius:999px;
      border:1px solid rgba(34,230,255,.28);
      background:rgba(34,230,255,.08);
      color:rgba(34,230,255,.95);
    }
    .strong{
      font-family:"Orbitron";
      font-weight:900;
      letter-spacing:.4px;
      color:rgba(255,255,255,.92);
      white-space:nowrap;
    }

    .totals{
      padding:14px 16px;
      border-top:1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.12);
    }
    .row{display:flex;justify-content:space-between;color:rgba(255,255,255,.82);font-size:13px;margin-bottom:10px}
    .row b{font-family:"Orbitron";font-weight:900;letter-spacing:1px;color:rgba(34,230,255,.95)}
    .row.final{
      margin-top:12px;
      padding-top:12px;
      border-top:1px dashed rgba(255,255,255,.18);
      font-size:14px;
    }
    .row.final b{color:#fff}

    /* Payment methods */
    .methodGrid{
      display:grid;
      grid-template-columns: 1fr;
      gap:12px;
      padding:14px 16px 16px;
    }
    .method{
      border-radius: var(--radiusL);
      border:1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.14);
      overflow:hidden;
      transition: transform .18s var(--easePop), filter .18s var(--easePop), border-color .18s var(--easePop);
      position:relative;
    }
    .method:hover{transform: translateY(-3px); filter: brightness(1.04); border-color: rgba(255,255,255,.20);}
    .methodHeader{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      padding:14px 14px;
      border-bottom: 1px solid rgba(255,255,255,.10);
      background: rgba(0,0,0,.10);
    }
    .methodLeft{display:flex;align-items:center;gap:12px;min-width:0}
    .iconBox{
      width:44px;height:44px;border-radius:14px;
      background: var(--gradBrand);
      display:grid;place-items:center;
      box-shadow: var(--shadowSoft);
      flex:0 0 auto;
    }
    .iconBox i{font-size:18px}
    .methodTitle{
      min-width:0;
    }
    .methodTitle .t{
      font-family:"Orbitron";
      font-weight:900;
      letter-spacing:.6px;
      font-size:13px;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .methodTitle .s{
      margin-top:4px;
      font-size:12px;
      color: rgba(255,255,255,.70);
    }
    .badge{
      font-family:"Orbitron";
      font-weight:900;
      letter-spacing:1px;
      font-size:10px;
      padding:7px 10px;
      border-radius:999px;
      border:1px solid rgba(255,255,255,.14);
      background: rgba(0,0,0,.18);
      color: rgba(255,255,255,.86);
      white-space:nowrap;
      display:flex;align-items:center;gap:8px;
    }
    .badge.good{
      border-color: rgba(0,255,157,.28);
      background: rgba(0,255,157,.08);
      color: rgba(0,255,157,.95);
    }

    .methodBody{
      padding:14px;
      display:grid;
      grid-template-columns: 210px 1fr;
      gap:14px;
      align-items:center;
    }
    @media (max-width: 520px){
      .methodBody{grid-template-columns:1fr}
    }

    .qr{
      width:210px;height:210px;
      border-radius: 18px;
      background: #fff;
      padding:10px;
      position:relative;
      border:1px solid rgba(255,255,255,.14);
      overflow:hidden;
      margin: 0 auto;
    }
    .qr img{width:100%;height:100%;object-fit:contain}
    .scan{
      position:absolute; left:0; top:-2px;
      width:100%; height:3px;
      background: linear-gradient(90deg, transparent, rgba(0,255,157,.95), transparent);
      animation: scan 2.2s linear infinite;
      opacity:.9;
    }
    @keyframes scan{
      0%{transform:translateY(0)}
      100%{transform:translateY(210px)}
    }

    .info{
      border-radius: 18px;
      border:1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.05);
      padding:12px;
    }
    .infoRow{
      display:flex;justify-content:space-between;gap:10px;
      font-size:12px;
      color: rgba(255,255,255,.78);
      padding:6px 0;
      border-bottom:1px solid rgba(255,255,255,.06);
    }
    .infoRow:last-child{border-bottom:none}
    .infoRow b{
      font-family:"Orbitron";
      letter-spacing:.6px;
      font-weight:900;
      color:#fff;
      max-width:60%;
      text-align:right;
    }

    .phoneBox{
      margin-top:10px;
      display:flex; align-items:center; justify-content:space-between; gap:10px;
      border-radius: 999px;
      border:1px solid rgba(34,230,255,.22);
      background: rgba(34,230,255,.08);
      padding:10px 12px;
    }
    .phoneBox .num{
      font-family:"Orbitron";
      font-weight:900;
      letter-spacing:1px;
      color: rgba(0,255,157,.95);
      white-space:nowrap;
    }
    .copyBtn{
      border:none; cursor:pointer;
      border-radius: 999px;
      padding:10px 12px;
      font-family:"Orbitron";
      font-weight:900;
      letter-spacing:1px;
      font-size:11px;
      background: rgba(0,0,0,.18);
      border:1px solid rgba(255,255,255,.14);
      color:#fff;
      transition: transform .18s var(--easePop), filter .18s var(--easePop);
      display:flex; align-items:center; gap:8px;
    }
    .copyBtn:hover{transform:translateY(-2px);filter:brightness(1.05)}

    .actions{
      margin-top:10px;
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:10px;
    }
    @media (max-width: 520px){
      .actions{grid-template-columns:1fr}
    }

    .btn{
      border:none; cursor:pointer;
      border-radius: 16px;
      padding:12px 12px;
      font-family:"Orbitron";
      font-weight:900;
      letter-spacing:1px;
      font-size:12px;
      display:flex; align-items:center; justify-content:center; gap:10px;
      transition: transform .18s var(--easePop), filter .18s var(--easePop);
      position:relative;
      overflow:hidden;
    }
    .btn:before{
      content:"";position:absolute;inset:0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
      transform: translateX(-120%);
      transition: transform .7s var(--easePop);
      opacity:.65;
    }
    .btn:hover:before{transform: translateX(120%)}
    .btn:active{transform:translateY(1px) scale(.99)}

    .btnWhats{
      background: linear-gradient(135deg, rgba(0,255,157,.95), rgba(34,230,255,.60));
      color:#061018;
      box-shadow: var(--shadowSoft);
    }
    .btnWhats:hover{transform:translateY(-2px);filter:brightness(1.05)}

    .btnClear{
      background: rgba(255,43,214,.12);
      border:1px solid rgba(255,43,214,.35);
      color: rgba(255,255,255,.92);
    }
    .btnClear:hover{transform:translateY(-2px);filter:brightness(1.06)}

    /* Empty */
    .empty{
      padding:24px 16px 20px;
      text-align:center;
      color: rgba(255,255,255,.78);
    }
    .empty .big{
      margin-top:10px;
      font-family:"Orbitron";
      font-weight:900;
      letter-spacing:1px;
      font-size:14px;
    }
    .empty .sub{
      margin-top:8px;
      color: rgba(255,255,255,.70);
      font-size:12px;
      line-height:1.4;
    }

    /* Toast */
    .toast{
      position:fixed; left:50%; bottom:20px; transform:translateX(-50%);
      z-index:3000;
      background:rgba(8,9,22,.88);
      border:1px solid rgba(255,255,255,.14);
      box-shadow:var(--shadowGlow);
      color:#fff;
      padding:10px 14px;
      border-radius:999px;
      font-size:13px;
      display:flex; align-items:center; gap:10px;
      opacity:0; pointer-events:none;
      transition: opacity .2s var(--easePop), transform .2s var(--easePop);
    }
    .toast.show{opacity:1; transform:translateX(-50%) translateY(-6px)}
    .toast i{color:rgba(34,230,255,.95)}
  </style>
</head>

<body>
  <div class="stars"></div>
  <div class="scanlines"></div>
  <div class="glow"></div>

  <header class="header">
    <div class="headerInner">
      <div class="brandLeft">
        <div class="brandMark" aria-hidden="true"></div>
        <div style="min-width:0">
          <div class="logo"><span>IG UNIVERSE</span></div>
          <div class="subtitle">Finaliza tu compra y envía el comprobante</div>
        </div>
      </div>

      <a class="backBtn" href="{{ url('/plataformas') }}">
        <i class="fa-solid fa-arrow-left"></i> VOLVER
      </a>
    </div>
  </header>

  <main class="wrap">
    <div class="grid">

      <!-- Resumen -->
      <section class="panel" id="summaryPanel">
        <div class="panelTitle">
          <h2><i class="fa-solid fa-receipt"></i> Resumen de compra</h2>
          <div class="status"><i class="fa-solid fa-circle-exclamation"></i> PENDIENTE</div>
        </div>

        <div class="list" id="summaryItems">
          <!-- items -->
        </div>

        <div class="totals">
          <div class="row"><span>SUBTOTAL</span> <b id="sumSubtotal">S/ 0.00</b></div>
          <div class="row"><span>ITEMS</span> <b id="sumItems">0</b></div>
          <div class="row final"><span>TOTAL A PAGAR</span> <b id="sumTotal">S/ 0.00</b></div>
        </div>
      </section>

      <!-- Métodos -->
      <section class="panel" id="methodsPanel">
        <div class="panelTitle">
          <h2><i class="fa-solid fa-qrcode"></i> Métodos de pago</h2>
          <div class="status"><i class="fa-solid fa-shield-halved"></i> SEGURO</div>
        </div>

        <div class="methodGrid">

          <!-- MÉTODO 1 -->
          <article class="method" data-method="1">
            <div class="methodHeader">
              <div class="methodLeft">
                <div class="iconBox"><i class="fa-brands fa-whatsapp"></i></div>
                <div class="methodTitle">
                  <div class="t">Yape / Plin</div>
                  <div class="s">Opción 1</div>
                </div>
              </div>
              <div class="badge good"><i class="fa-solid fa-star"></i> recomendado</div>
            </div>

            <div class="methodBody">
              <div class="qr">
                <div class="scan"></div>
                <img
                  src="/images/qr-yape.jpeg"
                  alt="QR Yape opción 1"
                  onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:954850003&color=4A6FFF&bgcolor=ffffff';"
                >
              </div>

              <div>
                <div class="info">
                  <div class="infoRow"><span>Titular</span> <b id="accName1">Igarlos R Mamani Q</b></div>
                  <div class="infoRow"><span>Número</span> <b id="accPhone1">954850003</b></div>
                  <div class="infoRow"><span>Monto</span> <b class="amount" data-amount-for="1">S/ 0.00</b></div>
                </div>

                <div class="phoneBox">
                  <div class="num" id="phoneShow1">907978279</div>
                  <button class="copyBtn" type="button" data-copy="907978279">
                    <i class="fa-regular fa-copy"></i> COPIAR
                  </button>
                </div>

                <div class="actions">
                  <button class="btn btnWhats" type="button" data-whatsapp="1">
                    <i class="fa-brands fa-whatsapp"></i> ENVIAR COMPROBANTE
                  </button>
                </div>
              </div>
            </div>
          </article>

          <!-- MÉTODO 2 -->
          <article class="method" data-method="2">
            <div class="methodHeader">
              <div class="methodLeft">
                <div class="iconBox"><i class="fa-brands fa-whatsapp"></i></div>
                <div class="methodTitle">
                  <div class="t">Yape / Plin</div>
                  <div class="s">Opción 2</div>
                </div>
              </div>
              <div class="badge"><i class="fa-solid fa-circle-dot"></i> </div>
            </div>

            <div class="methodBody">
              <div class="qr">
                <div class="scan"></div>
                <img
                  src="/images/qr-yape-2.jpeg"
                  alt="QR Yape opción 2"
                  onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:968238516&color=4A6FFF&bgcolor=ffffff';"
                >
              </div>

              <div>
                <div class="info">
                  <div class="infoRow"><span>Titular</span> <b id="accName2">Jennifer N Gallegos Q</b></div>
                  <div class="infoRow"><span>Número</span> <b id="accPhone2">968238516</b></div>
                  <div class="infoRow"><span>Monto</span> <b class="amount" data-amount-for="2">S/ 0.00</b></div>
                </div>

                <div class="phoneBox">
                  <div class="num" id="phoneShow2">968238516</div>
                  <button class="copyBtn" type="button" data-copy="968238516">
                    <i class="fa-regular fa-copy"></i> COPIAR
                  </button>
                </div>

                <div class="actions">
                  <button class="btn btnWhats" type="button" data-whatsapp="2">
                    <i class="fa-brands fa-whatsapp"></i> ENVIAR COMPROBANTE
                  </button>
                </div>
              </div>
            </div>
          </article>

          <!-- Nota -->
          <div class="line" style="margin-top:4px">
            <div class="lineTop">
              <div>
                <div class="lineName"><i class="fa-solid fa-circle-info" style="color:rgba(34,230,255,.95)"></i> Instrucciones</div>
                <div class="lineMeta">
                  1) Escanea QR o transfiere al número • 2) Paga el monto exacto • 3) Envía el comprobante por WhatsApp • 4) Te activamos rápido.
                </div>
              </div>
              <div class="lineAmt"><i class="fa-solid fa-bolt"></i> VIP</div>
            </div>
          </div>

        </div>
      </section>

    </div>
  </main>

  <div class="toast" id="toast">
    <i class="fa-solid fa-bolt"></i>
    <span id="toastText">Listo</span>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // CLAVES CORREGIDAS PARA CONECTAR CON EL CATÁLOGO (plataformas.blade.php)
    // El catálogo usa:
    //   localStorage -> 'ig_cart_pro'
    //   sessionStorage -> 'checkout_payload'
    // ============================================================
    const CART_KEY = 'ig_cart_pro';               // ← clave correcta
    const PAYLOAD_KEY = 'checkout_payload';       // ← clave correcta

    // WhatsApp destino
    const WHATSAPP_NUMBERS = {
      1: '51954850003',
      2: '51968238516'
    };

    const ACCOUNT_NAMES = {
      1: 'Igarlos R Mamani Q',
      2: 'Jennifer N Gallegos Q'
    };

    const ACCOUNT_PHONES = {
      1: '954850003',
      2: '968238516'
    };

    let payload = null;
    let cart = [];

    const $ = (id) => document.getElementById(id);
    const el = {
      summaryPanel: $('summaryPanel'),
      methodsPanel: $('methodsPanel'),
      summaryItems: $('summaryItems'),
      sumSubtotal: $('sumSubtotal'),
      sumItems: $('sumItems'),
      sumTotal: $('sumTotal'),
      toast: $('toast'),
      toastText: $('toastText'),
    };

    const money = (n) => 'S/ ' + Number(n || 0).toFixed(2);

    function escapeHTML(str) {
      return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function showToast(msg) {
      el.toastText.textContent = msg;
      el.toast.classList.add('show');
      clearTimeout(showToast._t);
      showToast._t = setTimeout(() => el.toast.classList.remove('show'), 1600);
    }

    function loadPayload() {
      // 1) Intentar obtener el payload desde sessionStorage (viene del checkout del catálogo)
      try {
        const p = sessionStorage.getItem(PAYLOAD_KEY);
        if (p) {
          payload = JSON.parse(p);
          if (payload && Array.isArray(payload.items)) {
            cart = payload.items;
            console.log('✅ Carrito cargado desde sessionStorage (checkout_payload)');
          }
        }
      } catch(e) { /* ignore */ }

      // 2) Si no hay, fallback a localStorage (carrito persistente)
      if (!cart || !cart.length) {
        try {
          const stored = localStorage.getItem(CART_KEY);
          if (stored) {
            cart = JSON.parse(stored);
            console.log('✅ Carrito cargado desde localStorage (ig_cart_pro)');
          }
        } catch(e) {
          cart = [];
        }
      }

      if (!cart.length) {
        renderEmpty();
        return;
      }

      renderSummary();
      syncAmounts();
    }

    function calc() {
      let subtotal = 0;
      let itemCount = 0;
      cart.forEach(item => {
        subtotal += Number(item.price || 0) * Number(item.quantity || 1);
        itemCount += Number(item.quantity || 1);
      });
      return { subtotal, total: subtotal, itemCount };
    }

    function renderSummary() {
      const { subtotal, total, itemCount } = calc();

      el.sumSubtotal.textContent = money(subtotal);
      el.sumItems.textContent = String(itemCount);
      el.sumTotal.textContent = money(total);

      if (!cart.length) {
        el.summaryItems.innerHTML = '<div class="empty">Carrito vacío</div>';
        return;
      }

      el.summaryItems.innerHTML = cart.map(item => {
        const price = Number(item.price || 0);
        const q = Number(item.quantity || 1);
        const lineTotal = price * q;

        return `
          <div class="line">
            <div class="lineTop">
              <div>
                <div class="lineName">${escapeHTML(item.name)}</div>
                <div class="lineMeta">Cantidad: ${q} • Precio: ${money(price)}</div>
              </div>
              <div class="lineAmt">${money(lineTotal)}</div>
            </div>
            <div class="lineTotal">
              <span class="pill"><i class="fa-solid fa-bag-shopping"></i> x${q}</span>
              <span class="strong">${money(lineTotal)}</span>
            </div>
          </div>
        `;
      }).join('');

      // Animación de entrada
      requestAnimationFrame(() => {
        el.summaryPanel.classList.add('is-in');
        el.methodsPanel.classList.add('is-in');
      });
    }

    function syncAmounts() {
      const { total } = calc();
      document.querySelectorAll('[data-amount-for]').forEach(el => {
        el.textContent = money(total);
      });
    }

    function renderEmpty() {
      el.summaryItems.innerHTML = `
        <div class="empty">
          <i class="fa-solid fa-cart-shopping" style="font-size:44px;color:rgba(34,230,255,.95);filter:drop-shadow(0 0 16px rgba(34,230,255,.22))"></i>
          <div class="big">NO HAY ITEMS PARA PAGAR</div>
          <div class="sub">Tu carrito está vacío. Vuelve al catálogo y agrega plataformas.</div>
          <div style="margin-top:14px;display:flex;justify-content:center">
            <a class="backBtn" href="{{ url('/plataformas') }}">
              <i class="fa-solid fa-arrow-left"></i> IR AL CATÁLOGO
            </a>
          </div>
        </div>
      `;
      requestAnimationFrame(() => {
        el.summaryPanel.classList.add('is-in');
        el.methodsPanel.classList.add('is-in');
      });
      el.sumSubtotal.textContent = money(0);
      el.sumItems.textContent = '0';
      el.sumTotal.textContent = money(0);
      syncAmounts();
    }

    function buildWhatsAppMessage(method) {
      const { total } = calc();
      const accountName = ACCOUNT_NAMES[method] || 'IG UNIVERSE';
      const accountPhone = ACCOUNT_PHONES[method] || '';

      let msg = `Hola IG UNIVERSE, ya realicé el pago ✅\n\n`;
      msg += `📋 *RESUMEN DE COMPRA:*\n`;
      msg += `━━━━━━━━━━━━━━━━━━━━\n`;

      cart.forEach((item, idx) => {
        const price = Number(item.price || 0);
        const q = Number(item.quantity || 1);
        msg += `${idx+1}. ${item.name}\n`;
        msg += `   Cantidad: ${q}\n`;
        msg += `   Precio: ${money(price)}\n\n`;
      });

      msg += `━━━━━━━━━━━━━━━━━━━━\n`;
      msg += `💰 *TOTAL PAGADO:* ${money(total)}\n`;
      msg += `📱 *Cuenta destino:* ${accountName} (${accountPhone})\n`;
      msg += `━━━━━━━━━━━━━━━━━━━━\n\n`;
      msg += `Adjunto el comprobante de pago.`;

      return msg;
    }

    async function copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text);
        showToast('Copiado: ' + text);
      } catch(e) {
        // Fallback
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        showToast('Copiado: ' + text);
      }
    }

    function clearCart() {
      if (confirm('¿Vaciar el carrito?')) {
        cart = [];
        try {
          localStorage.removeItem(CART_KEY);
          sessionStorage.removeItem(PAYLOAD_KEY);
        } catch(e) {}
        renderEmpty();
        showToast('Carrito vaciado');
      }
    }

    // Delegación de eventos (copiar, WhatsApp, vaciar)
    document.body.addEventListener('click', (e) => {
      const copy = e.target.closest('[data-copy]')?.dataset.copy;
      if (copy) {
        copyToClipboard(copy);
        return;
      }

      const wa = e.target.closest('[data-whatsapp]')?.dataset.whatsapp;
      if (wa) {
        if (!cart.length) {
          showToast('Carrito vacío');
          return;
        }
        const method = Number(wa);
        const phone = WHATSAPP_NUMBERS[method];
        if (!phone) {
          showToast('No hay WhatsApp destino');
          return;
        }
        const message = buildWhatsAppMessage(method);
        const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
        window.open(url, '_blank');
        showToast('Abriendo WhatsApp...');
        return;
      }

      if (e.target.closest('[data-clear]')) {
        clearCart();
        return;
      }
    });

    // Iniciar
    loadPayload();
  });
  </script>
</body>
</html>