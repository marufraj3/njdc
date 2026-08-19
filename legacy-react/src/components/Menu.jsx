import { useEffect, useRef, useState } from 'react'
import { useLocation } from 'react-router-dom'
import { menu } from '../lib/data'
import Link from './Link'

export default function Menu() {
  const [open, setOpen] = useState(false)
  const [active, setActive] = useState(null)
  const location = useLocation()
  const ref = useRef(null)

  useEffect(() => {
    setOpen(false)
    setActive(null)
  }, [location.pathname])

  useEffect(() => {
    const onClick = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setActive(null)
    }
    document.addEventListener('click', onClick)
    return () => document.removeEventListener('click', onClick)
  }, [])

  return (
    <section className="widget menus-expandable-widget max-view">
      <div className="menus-widget-container" style={{ '--home-label': "'হোম'" }} ref={ref}>
        <section className="widget menu-widget">
          <span
            id="menu-toggle"
            className="hamburger-menu-block"
            onClick={() => setOpen((v) => !v)}
          >
            <i className="hamburger-menu ph ph-list" />
            <span>মেনু নির্বাচন করুন</span>
          </span>

          <ul
            className={
              'menu-list menu-parent-unordered-list custom-items-center' + (open ? ' show-menu' : '')
            }
          >
            {menu.map((top, i) => {
              const hasChildren = top.groups && top.groups.length > 0
              const isHome = top.href === '/' && !hasChildren
              return (
                <li
                  key={i}
                  className={'megamenu-link' + (hasChildren ? ' menu-parent-list' : '')}
                  onMouseEnter={() => hasChildren && setActive(i)}
                  onMouseLeave={() => hasChildren && setActive(null)}
                >
                  {hasChildren ? (
                    <a
                      title={top.label}
                      href="#"
                      className="menu-parent-list-link"
                      onClick={(e) => {
                        e.preventDefault()
                        setActive(active === i ? null : i)
                      }}
                    >
                      {top.label}
                      <i className="menu-parent-list-link-icon ph ph-caret-double-down" />
                    </a>
                  ) : (
                    <Link
                      to={top.href}
                      title={top.label}
                      className={'menu-parent-list-link' + (isHome ? ' home-link' : '')}
                    >
                      {isHome ? '' : top.label}
                    </Link>
                  )}

                  {hasChildren && (
                    <div
                      className={'mega-menu-dropdown megaMenu' + (active === i ? ' show' : '')}
                    >
                      {top.groups.map((g, gi) => (
                        <div className="menu-child-box" key={gi}>
                          <h6 title={g.title} className="menu-child-title">
                            <a title={g.title} href="#" onClick={(e) => e.preventDefault()}>
                              <div>{g.title}</div>
                            </a>
                          </h6>
                          <ul className="menu-sub-child-unordered-list">
                            {g.items.map((it, ii) => (
                              <li className="menu-sub-child-list" key={ii}>
                                <Link title={it.label} className="menu-sub-child-link" to={it.href}>
                                  <div>{it.label}</div>
                                </Link>
                              </li>
                            ))}
                          </ul>
                        </div>
                      ))}
                    </div>
                  )}
                </li>
              )
            })}
          </ul>
        </section>
      </div>
    </section>
  )
}
