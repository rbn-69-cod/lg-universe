import { Component, signal } from '@angular/core';
import { LucideBolt, LucideBot, LucideChevronRight, LucideCircleAlert, LucideLock, LucideStar } from '@lucide/angular';

@Component({
  selector: 'app-home-menu-page',
  imports: [
    LucideBolt,
    LucideBot,
    LucideChevronRight,
    LucideCircleAlert,
    LucideLock,
    LucideStar,
  ],
  templateUrl: './home-menu-page.html',
  styleUrl: './home-menu-page.css',
})
export class HomeMenuPage {
  readonly showLoader = signal(true);

  constructor() {
    window.setTimeout(() => this.showLoader.set(false), 650);
  }
}
