import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../services/api.service';
import { Genre } from '../../models/book.model';
import { AlertComponent } from '../shared/alert/alert.component';
import { ConfirmDialogComponent } from '../shared/confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'app-genres',
  standalone: true,
  imports: [CommonModule, FormsModule, AlertComponent, ConfirmDialogComponent],
  templateUrl: './genres.component.html',
  styleUrls: ['./genres.component.scss']
})
export class GenresComponent implements OnInit {
  @ViewChild('alertRef') alertRef!: AlertComponent;
  @ViewChild('confirmRef') confirmRef!: ConfirmDialogComponent;

  genres: Genre[] = [];
  filtered: Genre[] = [];
  searchValue = '';
  newName = '';
  addError = '';
  loading = true;

  editGenre: Genre | null = null;
  editName = '';
  editError = '';

  constructor(private api: ApiService) {}

  ngOnInit() { this.loadGenres(); }

  loadGenres() {
    this.loading = true;
    this.api.getGenres().subscribe(d => {
      this.genres = d.genres;
      this.applyFilter();
      this.loading = false;
    });
  }

  applyFilter() {
    const q = this.searchValue.toLowerCase();
    this.filtered = q ? this.genres.filter(g => g.name.toLowerCase().includes(q)) : [...this.genres];
  }

  onSearch() { this.applyFilter(); }

  addGenre() {
    this.addError = '';
    if (!this.newName.trim()) { this.addError = 'Name is required.'; return; }
    this.api.addGenre(this.newName.trim()).subscribe(res => {
      if (res.success) {
        this.alertRef.show(`Genre "${this.newName}" added!`);
        this.newName = '';
        this.loadGenres();
      } else {
        this.alertRef.show(res.error || 'Failed.', 'error');
      }
    });
  }

  openEdit(g: Genre) { this.editGenre = g; this.editName = g.name; this.editError = ''; }
  closeEdit() { this.editGenre = null; }

  saveEdit() {
    this.editError = '';
    if (!this.editName.trim()) { this.editError = 'Name is required.'; return; }
    this.api.updateGenre(this.editGenre!.id, this.editName.trim()).subscribe(res => {
      if (res.success) {
        this.closeEdit();
        this.alertRef.show('Genre updated!');
        this.loadGenres();
      } else {
        this.alertRef.show(res.error || 'Update failed.', 'error');
      }
    });
  }

  async deleteGenre(g: Genre) {
    const ok = await this.confirmRef.open('Delete Genre', `Delete the genre "${g.name}"?`);
    if (!ok) return;
    this.api.deleteGenre(g.id).subscribe(res => {
      if (res.success) { this.alertRef.show('Genre deleted.'); this.loadGenres(); }
      else { this.alertRef.show(res.error || 'Delete failed.', 'error'); }
    });
  }
}
