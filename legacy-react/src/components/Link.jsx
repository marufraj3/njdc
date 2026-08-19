import { Link as RouterLink } from 'react-router-dom'

// Renders an internal <Link> for site paths, and a plain <a> for external / hash links.
export default function Link({ to, href, children, ...rest }) {
  const target = to || href || '#'
  const isInternal =
    typeof target === 'string' &&
    target.startsWith('/') &&
    !target.startsWith('//')

  if (isInternal) {
    return (
      <RouterLink to={target} {...rest}>
        {children}
      </RouterLink>
    )
  }
  return (
    <a href={target} target={target.startsWith('#') ? undefined : '_blank'} rel="noreferrer" {...rest}>
      {children}
    </a>
  )
}
