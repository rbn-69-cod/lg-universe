import { Component } from '@angular/core';

@Component({
  selector: 'app-lg-monogram-logo',
  template: `
    <svg viewBox="0 0 140 96" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <defs>
        <linearGradient id="lgMonogramStroke" x1="14" y1="12" x2="126" y2="84" gradientUnits="userSpaceOnUse">
          <stop stop-color="#08e7ff" />
          <stop offset="0.5" stop-color="#7c3cff" />
          <stop offset="1" stop-color="#ff197b" />
        </linearGradient>
        <linearGradient id="lgMonogramAccent" x1="48" y1="75" x2="129" y2="52" gradientUnits="userSpaceOnUse">
          <stop stop-color="#ffd166" />
          <stop offset="1" stop-color="#08e7ff" />
        </linearGradient>
        <filter id="lgMonogramGlow" x="-20%" y="-35%" width="140%" height="170%">
          <feGaussianBlur stdDeviation="3.2" result="blur" />
          <feColorMatrix
            in="blur"
            type="matrix"
            values="0 0 0 0 0.03 0 0 0 0 0.9 0 0 0 0 1 0 0 0 .82 0"
            result="glow"
          />
          <feMerge>
            <feMergeNode in="glow" />
            <feMergeNode in="SourceGraphic" />
          </feMerge>
        </filter>
      </defs>
      <path
        class="lg-shadow"
        d="M28 16V72H62"
        stroke="rgba(255,255,255,.16)"
        stroke-width="18"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      <path
        class="lg-main"
        d="M28 16V72H62"
        stroke="url(#lgMonogramStroke)"
        stroke-width="13"
        stroke-linecap="round"
        stroke-linejoin="round"
        filter="url(#lgMonogramGlow)"
      />
      <path
        class="lg-shadow"
        d="M94 18C74 18 61 31 61 49C61 68 75 80 94 80C108 80 119 74 124 64V52H98"
        stroke="rgba(255,255,255,.13)"
        stroke-width="18"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
      <path
        class="lg-main"
        d="M94 18C74 18 61 31 61 49C61 68 75 80 94 80C108 80 119 74 124 64V52H98"
        stroke="url(#lgMonogramStroke)"
        stroke-width="13"
        stroke-linecap="round"
        stroke-linejoin="round"
        filter="url(#lgMonogramGlow)"
      />
      <path class="lg-cut" d="M52 72H77" stroke="url(#lgMonogramAccent)" stroke-width="5" stroke-linecap="round" />
      <path class="lg-cut" d="M99 52H126V75" stroke="url(#lgMonogramAccent)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  `,
  styles: [
    `
      :host {
        width: var(--lg-logo-size, 102px);
        height: var(--lg-logo-size, 102px);
        display: grid;
        place-items: center;
        color: #fff;
        filter: drop-shadow(0 0 18px rgba(8, 231, 255, 0.42));
        animation: lgMonogramPulse 4.8s ease-in-out infinite;
      }

      svg {
        width: 100%;
        height: 100%;
        overflow: visible;
      }

      .lg-main {
        stroke-dasharray: 180;
        stroke-dashoffset: 0;
      }

      .lg-cut {
        opacity: 0.92;
      }

      @keyframes lgMonogramPulse {
        0%,
        100% {
          filter: drop-shadow(0 0 16px rgba(8, 231, 255, 0.34));
          transform: translateY(0);
        }

        50% {
          filter: drop-shadow(0 0 24px rgba(255, 25, 123, 0.36));
          transform: translateY(-1px);
        }
      }
    `,
  ],
  host: {
    role: 'img',
    'aria-label': 'LG',
  },
})
export class LgMonogramLogo {}
