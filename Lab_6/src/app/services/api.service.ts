import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Book, Genre, Lending, Stats } from '../models/book.model';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private base = '/api';

  constructor(private http: HttpClient) {}

  // stats
  getStats(): Observable<Stats> {
    return this.http.get<Stats>(`${this.base}/stats.php`);
  }

  // books
  getBooks(genreId?: number | string, search?: string): Observable<{ books: Book[] }> {
    let params = new HttpParams();
    if (genreId) params = params.set('genre_id', genreId.toString());
    if (search)  params = params.set('search', search);
    return this.http.get<{ books: Book[] }>(`${this.base}/books.php`, { params });
  }

  addBook(book: Partial<Book>): Observable<{ success: boolean; id?: number; error?: string }> {
    return this.http.post<any>(`${this.base}/books.php`, book);
  }

  updateBook(book: Partial<Book>): Observable<{ success: boolean; error?: string }> {
    return this.http.put<any>(`${this.base}/books.php`, book);
  }

  deleteBook(id: number): Observable<{ success: boolean; error?: string }> {
    return this.http.delete<any>(`${this.base}/books.php`, { params: { id: id.toString() } });
  }

  // genres
  getGenres(): Observable<{ genres: Genre[] }> {
    return this.http.get<{ genres: Genre[] }>(`${this.base}/genres.php`);
  }

  addGenre(name: string): Observable<{ success: boolean; id?: number; error?: string }> {
    return this.http.post<any>(`${this.base}/genres.php`, { name });
  }

  updateGenre(id: number, name: string): Observable<{ success: boolean; error?: string }> {
    return this.http.put<any>(`${this.base}/genres.php`, { id, name });
  }

  deleteGenre(id: number): Observable<{ success: boolean; error?: string }> {
    return this.http.delete<any>(`${this.base}/genres.php`, { params: { id: id.toString() } });
  }

  // lendings
  getLendings(activeOnly = false): Observable<{ lendings: Lending[] }> {
    let params = new HttpParams();
    if (activeOnly) params = params.set('active', '1');
    return this.http.get<{ lendings: Lending[] }>(`${this.base}/lendings.php`, { params });
  }

  addLending(lending: Partial<Lending>): Observable<{ success: boolean; id?: number; error?: string }> {
    return this.http.post<any>(`${this.base}/lendings.php`, lending);
  }

  markReturned(id: number, returned_date: string): Observable<{ success: boolean; error?: string }> {
    return this.http.put<any>(`${this.base}/lendings.php`, { id, returned_date });
  }

  deleteLending(id: number): Observable<{ success: boolean; error?: string }> {
    return this.http.delete<any>(`${this.base}/lendings.php`, { params: { id: id.toString() } });
  }
}
