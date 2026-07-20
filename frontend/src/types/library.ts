export type BookLoanStatus = 'dipinjam' | 'dikembalikan' | 'terlambat'

export interface BookLoan {
  id: string
  book_id: string
  student_id: string
  student_name: string | null
  student_nim: string | null
  borrowed_at: string
  due_at: string
  returned_at: string | null
  status: BookLoanStatus
  fine_amount: string | null
  created_at: string
}

export interface Book {
  id: string
  title: string
  author: string
  publisher: string | null
  isbn: string | null
  category: string | null
  stock: number
  is_active: boolean
  loans_count: number | null
  loans?: BookLoan[]
  created_at: string
}
