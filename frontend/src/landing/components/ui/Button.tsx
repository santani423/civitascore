import { forwardRef, type ButtonHTMLAttributes } from 'react'
import { cn } from '@/utils/cn'
import { buttonClass, type ButtonSize, type ButtonVariant } from './buttonClass'

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant
  size?: ButtonSize
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant, size, className, type = 'button', ...rest },
  ref,
) {
  return <button ref={ref} type={type} className={buttonClass(variant, size, className)} {...rest} />
})

/** Round icon-only button; always pass an aria-label. */
export const IconButton = forwardRef<HTMLButtonElement, ButtonHTMLAttributes<HTMLButtonElement>>(function IconButton(
  { className, type = 'button', ...rest },
  ref,
) {
  return (
    <button
      ref={ref}
      type={type}
      className={cn(
        'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lp-muted transition-colors duration-200 hover:bg-lp-sunken hover:text-lp-fg',
        className,
      )}
      {...rest}
    />
  )
})
