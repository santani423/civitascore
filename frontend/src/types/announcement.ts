export type AnnouncementTargetScope = 'universitas' | 'fakultas' | 'program_studi'

export interface Announcement {
  id: string
  title: string
  body: string
  target_scope: AnnouncementTargetScope
  target_id: string | null
  is_pinned: boolean
  published_at: string
  creator_name: string | null
  created_at: string
}
