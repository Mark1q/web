import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { Lending, Book } from '../../models/book.model';
import { AlertComponent } from '../shared/alert/alert.component';
import { ConfirmDialogComponent } from '../shared/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-lendings',
  standalone: true,
  imports: [CommonModule, FormsModule, AlertComponent, ConfirmDialogComponent],
  templateUrl: './lendings.component.html',
  styleUrls: ['./lendings.component.scss']
})
export class LendingsComponent implements OnInit {
  @ViewChild('alertRef') alertRef!: AlertComponent;
  @ViewChild('confirmRef') confirmRef!: ConfirmDialogComponent;

  lendings: Lending[] = [];
  books: Book[] = [];
  loading = true;
  activeTab: 'active' | 'all' = 'active';
  showNewModal = false;

  lendForm = {
    book_id: '',
    borrower_name: '',
    borrower_contact: '',
    lent_date: '',
    due_date: '',
    notes: ''
  };
  lendErrors: Record<string, string> = {};

  today = new Date().toISOString().slice(0, 10);

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.lendForm.lent_date = this.today;
    this.loadBooks();
    this.loadLendings();
  }

  loadBooks() { this.api.getBooks().subscribe(d => this.books = d.books); }

  loadLendings() {
    this.loading = true;
    this.api.getLendings(this.activeTab === 'active').subscribe(d => {
      this.lendings = d.lendings;
      this.loading = false;
    });
  }

  switchTab(tab: 'active' | 'all') { this.activeTab = tab; this.loadLendings(); }

  openNew() {
    this.lendForm = { book_id: '', borrower_name: '', borrower_contact: '', lent_date: this.today, due_date: '', notes: '' };
    this.lendErrors = {};
    this.showNewModal = true;
  }
  closeNew() { this.showNewModal = false; }

  submitLend() {
    this.lendErrors = {};
    if (!this.lendForm.book_id) { this.lendErrors['book_id'] = 'Required'; return; }
    if (!this.lendForm.borrower_name.trim()) { this.lendErrors['borrower_name'] = 'Required'; return; }
    if (!this.lendForm.lent_date) { this.lendErrors['lent_date'] = 'Required'; return; }
    const payload: any = { ...this.lendForm, book_id: +this.lendForm.book_id };
    this.api.addLending(payload).subscribe(res => {
      if (res.success) {
        this.closeNew();
        this.alertRef.show('Lending recorded!');
        this.loadLendings();
      } else {
        this.alertRef.show(res.error || 'Failed.', 'error');
      }
    });
  }

  async markReturned(id: number) {
    const ok = await this.confirmRef.open('Mark as Returned', 'Confirm that this book has been returned?', 'Yes, Returned');
    if (!ok) return;
    this.api.markReturned(id, this.today).subscribe(res => {
      if (res.success) { this.alertRef.show('Book marked as returned!'); this.loadLendings(); }
      else { this.alertRef.show(res.error || 'Failed.', 'error'); }
    });
  }

  async deleteLending(id: number) {
    const ok = await this.confirmRef.open('Delete Lending Record', 'Remove this lending record permanently?');
    if (!ok) return;
    this.api.deleteLending(id).subscribe(res => {
      if (res.success) { this.alertRef.show('Record deleted.'); this.loadLendings(); }
      else { this.alertRef.show(res.error || 'Delete failed.', 'error'); }
    });
  }

  isOverdue(lending: Lending): boolean {
    return !lending.returned_date && !!lending.due_date && lending.due_date < this.today;
  }
}
