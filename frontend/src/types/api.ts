/**
 * Mirrors the backend's uniform envelope (see app/Support/Http/ApiResponse.php):
 * {success, message, data, meta} on success, {success, message, errors} on failure.
 */
export interface ApiPaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface ApiSuccessResponse<T> {
  success: true
  message: string | null
  data: T
  meta?: ApiPaginationMeta
}

export interface ApiErrorResponse {
  success: false
  message: string
  errors?: Record<string, string[]>
}

export type ApiResponse<T> = ApiSuccessResponse<T> | ApiErrorResponse

/** Query params yang dipahami ListQuery di backend (lihat app/Support/Http/ListQuery.php). */
export interface ListParams {
  page?: number
  per_page?: number
  search?: string
  filter?: Record<string, string>
  sort?: string
}

export interface PaginatedResult<T> {
  data: T[]
  meta: ApiPaginationMeta
}
