import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ApiService } from '../../services/api.service';
import { Book, Genre, Stats } from '../../models/book.model';
import { AlertComponent } from '../shared/alert/alert.component';
import { ConfirmDialogComponent } from '../shared/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-browse',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AlertComponent, ConfirmDialogComponent],
  templateUrl: './browse.component.html',
  styleUrls: ['./browse.component.scss']
})
export class BrowseComponent implements OnInit {
  @ViewChild('alertRef') alertRef!: AlertComponent;
  @ViewChild('confirmRef') confirmRef!: ConfirmDialogComponent;

  stats: Stats = { total_books: 0, lent_out: 0, genres: 0, overdue: 0 };
  books: Book[] = [];
  genres: Genre[] = [];
  loading = true;

  searchValue = '';
  genreFilter = '';
  private searchSubject = new Subject<string>();

  private readonly STORAGE_KEY = 'bookshelf_last_filter';
  lastFilterLabel = '';

  private get currentGenreName(): string {
    return this.genres.find(g => String(g.id) === String(this.genreFilter))?.name || '';
  }

  // detail modal
  selectedBook: Book | null = null;
  editMode = false;
  editData: Partial<Book> = {};

  // lend modal
  lendBookId: number | null = null;
  lendForm = { borrower_name: '', borrower_contact: '', lent_date: '', due_date: '', notes: '' };
  lendErrors: Record<string, string> = {};

  editErrors: Record<string, string> = {};

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.lendForm.lent_date = new Date().toISOString().slice(0, 10);
    this.loadStats();
    this.loadGenres();
    this.restoreSavedFilter();
    this.loadBooks();
    this.searchSubject.pipe(debounceTime(400), distinctUntilChanged()).subscribe(() => this.loadBooks());
  }

  loadStats() {
    this.api.getStats().subscribe(s => this.stats = s);
  }

  loadGenres() {
    this.api.getGenres().subscribe(d => this.genres = d.genres);
  }

  loadBooks() {
    this.loading = true;
    this.saveFilterToStorage();
    this.api.getBooks(this.genreFilter || undefined, this.searchValue || undefined).subscribe(d => {
      this.books = d.books;
      this.loading = false;
    });
  }

  private restoreSavedFilter() {
    try {
      const saved = sessionStorage.getItem(this.STORAGE_KEY);
      if (!saved) return;
      const { genreId, search } = JSON.parse(saved);
      if (genreId) this.genreFilter = genreId;
      if (search)  this.searchValue = search;
    } catch {}
  }

  private saveFilterToStorage() {
    // Show the PREVIOUS filter as the "last filter" label before overwriting
    try {
      const prev = sessionStorage.getItem(this.STORAGE_KEY);
      if (prev) {
        const { genreId, search, genreName } = JSON.parse(prev);
        if (genreId || search) {
          const parts: string[] = [];
          if (genreId) parts.push(`genre: "${genreName}"`);
          if (search)  parts.push(`search: "${search}"`);
          this.lastFilterLabel = `Last filter — ${parts.join(', ')}`;
        }
      }
    } catch {}

    // Save current filter
    sessionStorage.setItem(this.STORAGE_KEY, JSON.stringify({
      genreId: this.genreFilter,
      search: this.searchValue,
      genreName: this.currentGenreName
    }));
  }

  clearLastFilter() { this.lastFilterLabel = ''; }

  onSearch(val: string) {
    this.searchValue = val;
    this.searchSubject.next(val);
  }

  onGenreChange() { this.loadBooks(); }

  openDetail(book: Book) {
    this.selectedBook = book;
    this.editMode = false;
  }

  openEdit(book: Book) {
    this.selectedBook = book;
    this.editMode = true;
    this.editErrors = {};
    this.editData = { ...book };
  }

  async deleteBook(book: Book) {
    this.selectedBook = null;
    const ok = await this.confirmRef.open('Delete Book', `Delete "${book.title}" by ${book.author}? This cannot be undone.`);
    if (!ok) return;
    this.api.deleteBook(book.id).subscribe(res => {
      if (res.success) {
        this.alertRef.show('Book deleted.');
        this.loadBooks(); this.loadStats();
      } else {
        this.alertRef.show(res.error || 'Delete failed.', 'error');
      }
    });
  }

  saveEdit() {
    this.editErrors = {};
    if (!this.editData.title?.trim()) { this.editErrors['title'] = 'Required'; return; }
    if (!this.editData.author?.trim()) { this.editErrors['author'] = 'Required'; return; }
    this.api.updateBook(this.editData).subscribe(res => {
      if (res.success) {
        this.selectedBook = null;
        this.alertRef.show('Book updated successfully!');
        this.loadBooks(); this.loadStats();
      } else {
        this.alertRef.show(res.error || 'Update failed.', 'error');
      }
    });
  }

  openLend(bookId: number) {
    this.selectedBook = null;
    this.lendBookId = bookId;
    this.lendForm = { borrower_name: '', borrower_contact: '', lent_date: new Date().toISOString().slice(0, 10), due_date: '', notes: '' };
    this.lendErrors = {};
  }

  submitLend() {
    this.lendErrors = {};
    if (!this.lendForm.borrower_name.trim()) { this.lendErrors['borrower_name'] = 'Required'; return; }
    if (!this.lendForm.lent_date) { this.lendErrors['lent_date'] = 'Required'; return; }
    this.api.addLending({ book_id: this.lendBookId!, ...this.lendForm }).subscribe(res => {
      if (res.success) {
        this.lendBookId = null;
        this.alertRef.show('Book lent successfully!');
        this.loadBooks(); this.loadStats();
      } else {
        this.alertRef.show(res.error || 'Failed to lend.', 'error');
      }
    });
  }

  closeAll() { this.selectedBook = null; this.lendBookId = null; }

  coverStyle(color: string) { return { background: color }; }

  today() { return new Date().getFullYear(); }
}
