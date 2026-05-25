export interface Book {
  id: number;
  title: string;
  author: string;
  genre_id: number | null;
  genre_name?: string;
  pages: number | null;
  published_year: number | null;
  isbn: string;
  description: string;
  cover_color: string;
  created_at?: string;
  is_lent?: boolean;
}

export interface Genre {
  id: number;
  name: string;
  book_count?: number;
}

export interface Lending {
  id: number;
  book_id: number;
  title?: string;
  author?: string;
  cover_color?: string;
  borrower_name: string;
  borrower_contact: string;
  lent_date: string;
  due_date: string | null;
  returned_date: string | null;
  notes: string;
}

export interface Stats {
  total_books: number;
  lent_out: number;
  genres: number;
  overdue: number;
}
