import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { ApiService } from '../../services/api.service';
import { Book, Genre } from '../../models/book.model';
import { AlertComponent } from '../shared/alert/alert.component';
import { ConfirmDialogComponent } from '../shared/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-manage',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AlertComponent, ConfirmDialogComponent],
  templateUrl: './manage.component.html',
  styleUrls: ['./manage.component.scss']
})
export class ManageComponent implements OnInit {
  @ViewChild('alertRef') alertRef!: AlertComponent;
  @ViewChild('confirmRef') confirmRef!: ConfirmDialogComponent;

  books: Book[] = [];
  genres: Genre[] = [];
  loading = true;
  searchValue = '';
  genreFilter = '';
  private searchSubject = new Subject<string>();

  editBook: Book | null = null;
  editData: Partial<Book> = {};
  editErrors: Record<string, string> = {};

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.loadGenres();
    this.loadBooks();
    this.searchSubject.pipe(debounceTime(400), distinctUntilChanged()).subscribe(() => this.loadBooks());
  }

  loadGenres() { this.api.getGenres().subscribe(d => this.genres = d.genres); }

  loadBooks() {
    this.loading = true;
    this.api.getBooks(this.genreFilter || undefined, this.searchValue || undefined).subscribe(d => {
      this.books = d.books;
      this.loading = false;
    });
  }

  onSearch(val: string) { this.searchValue = val; this.searchSubject.next(val); }
  onGenreChange() { this.loadBooks(); }

  openEdit(book: Book) {
    this.editBook = book;
    this.editData = { ...book };
    this.editErrors = {};
  }

  closeEdit() { this.editBook = null; }

  today() { return new Date().getFullYear(); }

  saveEdit() {
    this.editErrors = {};
    if (!this.editData.title?.trim()) { this.editErrors['title'] = 'Required'; return; }
    if (!this.editData.author?.trim()) { this.editErrors['author'] = 'Required'; return; }
    this.api.updateBook(this.editData).subscribe(res => {
      if (res.success) {
        this.closeEdit();
        this.alertRef.show('Book updated!');
        this.loadBooks();
      } else {
        this.alertRef.show(res.error || 'Update failed.', 'error');
      }
    });
  }

  async deleteBook(id: number, title: string) {
    const ok = await this.confirmRef.open('Delete Book', `Delete "${title}"? This cannot be undone.`);
    if (!ok) return;
    this.api.deleteBook(id).subscribe(res => {
      if (res.success) { this.alertRef.show('Book deleted.'); this.loadBooks(); }
      else { this.alertRef.show(res.error || 'Delete failed.', 'error'); }
    });
  }
}
