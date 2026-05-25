import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { Genre } from '../../models/book.model';
import { AlertComponent } from '../shared/alert/alert.component';

@Component({
  selector: 'app-add-book',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AlertComponent],
  templateUrl: './add-book.component.html',
  styleUrls: ['./add-book.component.scss']
})
export class AddBookComponent implements OnInit {
  @ViewChild('alertRef') alertRef!: AlertComponent;

  genres: Genre[] = [];
  saving = false;
  errors: Record<string, string> = {};

  form = {
    title: '',
    author: '',
    genre_id: '',
    pages: '',
    published_year: '',
    isbn: '',
    cover_color: '#4a6fa5',
    description: ''
  };

  get previewTitle() { return this.form.title || 'Preview'; }

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.api.getGenres().subscribe(d => this.genres = d.genres);
  }

  today() { return new Date().getFullYear(); }

  validate(): boolean {
    this.errors = {};
    if (!this.form.title.trim()) { this.errors['title'] = 'Title is required.'; return false; }
    if (!this.form.author.trim()) { this.errors['author'] = 'Author is required.'; return false; }
    if (this.form.pages && (isNaN(+this.form.pages) || +this.form.pages <= 0)) {
      this.errors['pages'] = 'Must be a positive number.'; return false;
    }
    const y = +this.form.published_year;
    if (this.form.published_year && (y < 1000 || y > this.today())) {
      this.errors['published_year'] = `Enter a valid year (1000–${this.today()}).`; return false;
    }
    return true;
  }

  submit() {
    if (!this.validate()) return;
    this.saving = true;
    const payload: any = {
      title: this.form.title.trim(),
      author: this.form.author.trim(),
      genre_id: this.form.genre_id || null,
      pages: this.form.pages || null,
      published_year: this.form.published_year || null,
      isbn: this.form.isbn.trim(),
      cover_color: this.form.cover_color,
      description: this.form.description.trim()
    };
    this.api.addBook(payload).subscribe(res => {
      this.saving = false;
      if (res.success) {
        this.alertRef.show('Book added successfully! <a href="/browse">Browse library →</a>', 'success', true);
        this.form = { title: '', author: '', genre_id: '', pages: '', published_year: '', isbn: '', cover_color: '#4a6fa5', description: '' };
        this.errors = {};
      } else {
        this.alertRef.show(res.error || 'Failed to add book.', 'error');
      }
    });
  }
}
