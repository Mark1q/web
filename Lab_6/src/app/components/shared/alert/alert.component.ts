import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';

export interface AlertMessage {
  message: string;
  type: 'success' | 'error' | 'info';
  html?: boolean;
}

@Component({
  selector: 'app-alert',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div *ngFor="let alert of alerts" class="alert" [ngClass]="'alert-' + alert.type">
      <span>{{ alert.type === 'success' ? '✓' : alert.type === 'error' ? '✗' : 'ℹ' }}</span>
      <span *ngIf="!alert.html">{{ alert.message }}</span>
      <span *ngIf="alert.html" [innerHTML]="alert.message"></span>
    </div>
  `
})
export class AlertComponent {
  alerts: AlertMessage[] = [];

  show(message: string, type: 'success' | 'error' | 'info' = 'success', html = false) {
    const alert: AlertMessage = { message, type, html };
    this.alerts.unshift(alert);
    setTimeout(() => {
      const idx = this.alerts.indexOf(alert);
      if (idx > -1) this.alerts.splice(idx, 1);
    }, 4000);
  }
}
