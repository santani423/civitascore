import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { LoaderCircle, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'

type Variant = 'primary' | 'secondary' | 'ghost' | 'soft' | 'danger' | 'inverse'
type Size = 'sm' | 'md' | 'lg'

const base =
  'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl font-medium transition-[background-color,color,box-shadow,transform] duration-150 active:translate-y-px disabled:pointer-events-none disabled:opacity-50'

const variants: Record<Variant, string> = {
  primary: 'bg-one-brand text-one-onbrand shadow-sm hover:bg-one-brand/90',
  secondary: 'border border-one-line bg-one-card text-one-fg shadow-soft hover:border-one-line2 hover:bg-one-sunken',
  ghost: 'text-one-muted hover:bg-one-sunken hover:text-one-fg',
  soft: 'bg-one-royal/10 text-one-royal hover:bg-one-royal/15',
  danger: 'bg-one-bad text-white hover:bg-one-bad/90',
  inverse: 'bg-white text-one-hero shadow-sm hover:bg-white/90',
}

const sizes: Record<Size, string> = {
  sm: 'h-8 px-3 text-[13px]',
  md: 'h-10 px-4 text-sm',
  lg: 'h-12 px-5 text-[15px]',
}

const iconSize: Record<Size, number> = { sm: 15, md: 16, lg: 18 }

interface Common {
  variant?: Variant
  size?: Size
  icon?: LucideIcon
  iconRight?: LucideIcon
  loading?: boolean
  block?: boolean
  className?: string
  children?: ReactNode
}

type ButtonProps = Common & ButtonHTMLAttributes<HTMLButtonElement> & { to?: undefined }
type LinkProps = Common & { to: string; 'aria-label'?: string; onClick?: () => void }

export const Button = forwardRef<HTMLButtonElement, ButtonProps | LinkProps>(function Button(props, ref) {
  const { variant = 'primary', size = 'md', icon: Icon, iconRight: IconRight, loading, block, className, children, ...rest } = props
  const classes = cn(base, variants[variant], sizes[size], block && 'w-full', className)
  const content = (
    <>
      {loading ? <LoaderCircle size={iconSize[size]} className="animate-spin" aria-hidden /> : Icon && <Icon size={iconSize[size]} aria-hidden />}
      {children}
      {IconRight && <IconRight size={iconSize[size]} aria-hidden />}
    </>
  )

  if ('to' in rest && rest.to !== undefined) {
    const { to, ...linkRest } = rest as LinkProps
    return (
      <Link to={to} className={classes} {...linkRest}>
        {content}
      </Link>
    )
  }

  const { type = 'button', disabled, ...btnRest } = rest as ButtonHTMLAttributes<HTMLButtonElement>
  return (
    <button ref={ref} type={type} disabled={disabled || loading} aria-busy={loading || undefined} className={classes} {...btnRest}>
      {content}
    </button>
  )
})

interface IconButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  icon: LucideIcon
  label: string
  size?: 'sm' | 'md'
  variant?: 'ghost' | 'secondary' | 'soft'
  active?: boolean
  badge?: number
}

export const IconButton = forwardRef<HTMLButtonElement, IconButtonProps>(function IconButton(
  { icon: Icon, label, size = 'md', variant = 'ghost', active, badge, className, ...rest },
  ref,
) {
  return (
    <button
      ref={ref}
      type="button"
      aria-label={label}
      title={label}
      className={cn(
        'relative inline-flex shrink-0 items-center justify-center rounded-xl transition-colors',
        size === 'sm' ? 'h-8 w-8' : 'h-10 w-10',
        variant === 'ghost' && 'text-one-muted hover:bg-one-sunken hover:text-one-fg',
        variant === 'secondary' && 'border border-one-line bg-one-card text-one-muted hover:bg-one-sunken hover:text-one-fg',
        variant === 'soft' && 'bg-one-royal/10 text-one-royal hover:bg-one-royal/15',
        active && 'bg-one-royal/10 text-one-royal',
        className,
      )}
      {...rest}
    >
      <Icon size={size === 'sm' ? 16 : 18} aria-hidden />
      {badge !== undefined && badge > 0 && (
        <span
          aria-hidden
          className="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-one-bad px-1 text-[10px] font-semibold leading-none text-white ring-2 ring-one-canvas"
        >
          {badge > 9 ? '9+' : badge}
        </span>
      )}
    </button>
  )
})
