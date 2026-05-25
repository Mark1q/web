import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-confirm-dialog',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div *ngIf="visible" class="confirm-overlay" (click)="onOverlay($event)">
      <div class="confirm-box">
        <h3>{{ title }}</h3>
        <p>{{ message }}</p>
        <div class="confirm-btns">
          <button class="btn btn-ghost" (click)="resolve(false)">Cancel</button>
          <button class="btn btn-danger" (click)="resolve(true)">{{ okLabel }}</button>
        </div>
      </div>
    </div>
  `
})
export class ConfirmDialogComponent {
  visible = false;
  title = 'Are you sure?';
  message = 'This action cannot be undone.';
  okLabel = 'Delete';
  private _resolve!: (val: boolean) => void;

  open(title: string, message: string, okLabel = 'Delete'): Promise<boolean> {
    this.title = title;
    this.message = message;
    this.okLabel = okLabel;
    this.visible = true;
    return new Promise(res => this._resolve = res);
  }

  resolve(val: boolean) {
    this.visible = false;
    this._resolve(val);
  }

  onOverlay(e: MouseEvent) {
    if ((e.target as Element).classList.contains('confirm-overlay')) this.resolve(false);
  }
}
