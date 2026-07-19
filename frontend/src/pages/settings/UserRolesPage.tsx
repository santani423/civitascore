import { useEffect, useState } from 'react'
import { Search, X } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { EmptyState } from '@/components/ui/EmptyState'
import { usePermission } from '@/hooks/usePermission'
import { roleService, userRoleService, userService } from '@/services/userManagementService'
import type { NormalizedApiError } from '@/services/api'
import type { Role, UserSummary } from '@/types/userManagement'
import { ROUTES } from '@/constants/routes'

export function UserRolesPage() {
  const canAssign = usePermission('user_roles.create')
  const canRevoke = usePermission('user_roles.delete')

  const [query, setQuery] = useState('')
  const [results, setResults] = useState<UserSummary[]>([])
  const [isSearching, setIsSearching] = useState(false)
  const [searchError, setSearchError] = useState<string | null>(null)

  const [selectedUser, setSelectedUser] = useState<UserSummary | null>(null)
  const [userRoles, setUserRoles] = useState<Role[] | null>(null)
  const [rolesError, setRolesError] = useState<string | null>(null)

  const [allRoles, setAllRoles] = useState<Role[]>([])
  const [selectedRoleId, setSelectedRoleId] = useState('')
  const [expiresAt, setExpiresAt] = useState('')
  const [actionError, setActionError] = useState<string | null>(null)
  const [isAssigning, setIsAssigning] = useState(false)
  const [revokingRoleId, setRevokingRoleId] = useState<string | null>(null)

  useEffect(() => {
    roleService.index({ per_page: 100 }).then((result) => setAllRoles(result.data))
  }, [])

  useEffect(() => {
    if (query.trim().length < 2) {
      setResults([])
      return
    }

    const timeout = setTimeout(() => {
      setIsSearching(true)
      setSearchError(null)

      userService
        .index({ search: query, per_page: 10 })
        .then((result) => setResults(result.data))
        .catch((error: NormalizedApiError) => setSearchError(error.message))
        .finally(() => setIsSearching(false))
    }, 350)

    return () => clearTimeout(timeout)
  }, [query])

  const loadUserRoles = (user: UserSummary) => {
    setSelectedUser(user)
    setUserRoles(null)
    setRolesError(null)

    userRoleService
      .index(user.id)
      .then((roles) => setUserRoles(roles))
      .catch((error: NormalizedApiError) => setRolesError(error.message))
  }

  const handleAssign = async () => {
    if (!selectedUser || !selectedRoleId) return
    setActionError(null)
    setIsAssigning(true)

    try {
      const roles = await userRoleService.assign(selectedUser.id, selectedRoleId, expiresAt || null)
      setUserRoles(roles)
      setSelectedRoleId('')
      setExpiresAt('')
    } catch (error) {
      setActionError((error as NormalizedApiError).message ?? 'Gagal menambahkan role.')
    } finally {
      setIsAssigning(false)
    }
  }

  const handleRevoke = async (roleId: string) => {
    if (!selectedUser) return
    setActionError(null)
    setRevokingRoleId(roleId)

    try {
      await userRoleService.revoke(selectedUser.id, roleId)
      setUserRoles((current) => current?.filter((role) => role.id !== roleId) ?? null)
    } catch (error) {
      setActionError((error as NormalizedApiError).message ?? 'Gagal mencabut role.')
    } finally {
      setRevokingRoleId(null)
    }
  }

  const assignableRoles = allRoles.filter((role) => !userRoles?.some((assigned) => assigned.id === role.id))

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Role Pengguna"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Role Pengguna' }]}
      />

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,320px)_1fr]">
        <Card title="Cari Pengguna" noPadding>
          <div className="border-b border-border p-3">
            <Input
              placeholder="Cari nama atau email..."
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              leftIcon={<Search className="size-4" />}
            />
          </div>

          {searchError && (
            <Alert variant="danger" className="m-3">
              {searchError}
            </Alert>
          )}

          <div className="max-h-96 overflow-y-auto">
            {isSearching && <p className="p-3 text-sm text-ink-tertiary">Mencari...</p>}
            {!isSearching && query.trim().length >= 2 && results.length === 0 && (
              <p className="p-3 text-sm text-ink-tertiary">Tidak ada pengguna ditemukan.</p>
            )}
            {results.map((user) => (
              <button
                key={user.id}
                type="button"
                onClick={() => loadUserRoles(user)}
                className={`flex w-full flex-col gap-0.5 border-b border-border px-3 py-2.5 text-left last:border-b-0 hover:bg-surface-hover ${
                  selectedUser?.id === user.id ? 'bg-primary-50 dark:bg-primary-950' : ''
                }`}
              >
                <span className="text-sm font-medium text-ink-primary">{user.name}</span>
                <span className="text-xs text-ink-tertiary">{user.email}</span>
              </button>
            ))}
          </div>
        </Card>

        <Card title={selectedUser ? `Role — ${selectedUser.name}` : 'Role Pengguna'} noPadding>
          {!selectedUser ? (
            <EmptyState title="Pilih pengguna" description="Cari dan pilih pengguna di sebelah kiri untuk mengelola role-nya." />
          ) : (
            <div className="flex flex-col gap-4 p-4">
              {actionError && (
                <Alert variant="danger" onDismiss={() => setActionError(null)}>
                  {actionError}
                </Alert>
              )}

              {rolesError && <Alert variant="danger">{rolesError}</Alert>}

              {userRoles === null && !rolesError ? (
                <p className="text-sm text-ink-tertiary">Memuat role...</p>
              ) : (
                <div className="flex flex-wrap gap-2">
                  {userRoles && userRoles.length === 0 && (
                    <p className="text-sm text-ink-tertiary">Pengguna ini belum punya role.</p>
                  )}
                  {userRoles?.map((role) => (
                    <Badge key={role.id} variant="primary" className="flex items-center gap-1.5">
                      {role.name}
                      {canRevoke && (
                        <button
                          type="button"
                          onClick={() => handleRevoke(role.id)}
                          disabled={revokingRoleId === role.id}
                          aria-label={`Cabut role ${role.name}`}
                        >
                          <X className="size-3" />
                        </button>
                      )}
                    </Badge>
                  ))}
                </div>
              )}

              {canAssign && (
                <div className="flex flex-wrap items-end gap-3 border-t border-border pt-4">
                  <Select
                    label="Tambah Role"
                    className="min-w-48"
                    value={selectedRoleId}
                    onChange={(event) => setSelectedRoleId(event.target.value)}
                    options={assignableRoles.map((role) => ({ value: role.id, label: role.name }))}
                    placeholder="Pilih role"
                  />
                  <Input
                    label="Berlaku Hingga (opsional)"
                    type="date"
                    value={expiresAt}
                    onChange={(event) => setExpiresAt(event.target.value)}
                  />
                  <Button isLoading={isAssigning} disabled={!selectedRoleId} onClick={handleAssign}>
                    Tambahkan
                  </Button>
                </div>
              )}
            </div>
          )}
        </Card>
      </div>
    </div>
  )
}
