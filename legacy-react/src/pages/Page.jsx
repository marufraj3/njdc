import { useState } from 'react'
import { useLocation } from 'react-router-dom'
import { pages } from '../lib/data'
import Html from '../lib/Html'
import Link from '../components/Link'
import NotFound from './NotFound'

function ShareWidget() {
  const url = typeof window !== 'undefined' ? window.location.href : ''
  const enc = encodeURIComponent(url)
  const links = [
    ['Facebook', 'ph-fill ph-facebook-logo social-content-share-widget-fb-icon', 'https://www.facebook.com/sharer/sharer.php?u=' + enc],
    ['X (Formerly Twitter)', 'ph ph-x-logo social-content-share-widget-twitter-icon', 'https://twitter.com/intent/tweet?url=' + enc],
    ['WhatsApp', 'ph-fill ph-whatsapp-logo social-content-share-widget-whatsapp-icon', 'https://wa.me/?text=' + enc],
    ['LinkedIn', 'ph-fill ph-linkedin-logo social-content-share-widget-linkedin-icon', 'https://www.linkedin.com/sharing/share-offsite/?url=' + enc],
    ['Viber', 'ph-fill ph-chat-circle-text social-content-share-widget-viber-icon', 'viber://forward?text=' + enc],
    ['Messenger', 'ph-fill ph-messenger-logo social-content-share-widget-messenger-icon', 'https://www.facebook.com/dialog/send?link=' + enc],
  ]
  return (
    <section className="widget social-content-share-widget">
      <div>
        <p className="social-content-share-widget-title">এই কনটেন্টটি শেয়ার করতে ক্লিক করুন</p>
        <div className="social-content-share-widget-social-icons">
          {links.map(([title, cls, href]) => (
            <a
              key={title}
              className="social-content-share-widget-social-icons-link"
              href={href}
              rel="noopener noreferrer"
              target="_blank"
              title={title}
            >
              <i className={cls} />
            </a>
          ))}
        </div>
      </div>
      <div>
        <div className="widget eps-opinion-popup-widget">
          <div className="eps-opinion-trigger-wrapper">
            <p className="social-content-share-widget-title"> </p>
            <button className="eps-opinion-btn" type="button">
              <span />
              <span>আপনার মতামত প্রদান করুন</span>
              <i className="ph ph-chat-circle-text" />
            </button>
          </div>
        </div>
      </div>
    </section>
  )
}

function Pagination({ total }) {
  return (
    <>
      <ul className="pagination">
        <li className="page-item active">
          <a className="page-link" tabIndex={0}>
            ১
          </a>
        </li>
      </ul>
      <div className="pagination-meta">
        <span />
        <span />
        <span className="pagination-counts">
          দেখছেন ১ থেকে {toBn(total)} পর্যন্ত, মোট {toBn(total)} এন্ট্রি
        </span>
      </div>
    </>
  )
}

const BN = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯']
function toBn(n) {
  return String(n)
    .split('')
    .map((c) => (/[0-9]/.test(c) ? BN[+c] : c))
    .join('')
}

function ContentPage({ page }) {
  const [fileIdx, setFileIdx] = useState(0)
  const files = page.files || []

  return (
    <div className="widget content-viewer-widget">
      <div className="content-update-block">
        <p>{page.updated}</p>
      </div>
      <div className="widget print-widget">
        <div />
        <div onClick={() => window.print()} title="প্রিন্ট">
          <i className="ph ph-printer" />
        </div>
      </div>
      <div className="widget title-with-image-content-widget">
        <h2>{page.title}</h2>
        {page.chips && page.chips.length > 0 && (
          <p className="chips">
            {page.chips.map((c, i) => (
              <span className="basic-chip" key={i}>
                {c}
              </span>
            ))}
          </p>
        )}

        {page.images && page.images.length > 0 && (
          <div className="image-list" style={{ display: 'block' }}>
            {page.images.map((src, i) => (
              <img key={i} src={src} alt="" />
            ))}
          </div>
        )}

        {page.html ? <Html html={page.html} /> : null}

        {files.length > 0 && (
          <div className="title-with-image-content-widget-content-files active">
            <div className="chip-list">
              {files.map((f, i) => (
                <div
                  className={'chip' + (i === fileIdx ? ' active' : '')}
                  key={i}
                  title={f.label}
                  onClick={() => setFileIdx(i)}
                >
                  {f.label.length > 22 ? f.label.slice(0, 20) + '...' : f.label}{' '}
                  <a
                    href={f.href}
                    target="_blank"
                    rel="noreferrer"
                    style={{
                      textDecoration: 'none',
                      color: 'inherit',
                      backgroundColor: '#0002',
                      padding: '8px',
                    }}
                    title="Click here to download"
                  >
                    <i className="ph ph-download-simple download-icon" />
                  </a>
                </div>
              ))}
            </div>
            <div
              className="title-with-image-content-widget-content-pdf"
              style={{ height: '700px' }}
            >
              <object
                className="active"
                data={files[fileIdx].href}
                height="600"
                type="application/pdf"
                width="100%"
              >
                <h3>ফাইল প্রিভিউ ওয়েব ব্রাউজারে সমর্থিত নয়</h3>
                <p>{files[fileIdx].label}</p>
                <a href={files[fileIdx].href} target="_blank" rel="noreferrer">
                  <i className="ph ph-download-simple download-icon" />
                  <span>ডাউনলোড করুন</span>
                </a>
              </object>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

function TablePage({ page }) {
  return (
    <div className="widget datatable-widget">
      <div className="title-bar">
        <h2>{page.title}</h2>
      </div>
      <div className="container-group">
        <div className="searchbar">
          <input placeholder="অনুসন্ধান" type="text" />
          <button id="search-btn">
            <i className="ph ph-magnifying-glass" />
          </button>
        </div>
        <div className="pagesize-dropdown">
          <label>পৃষ্ঠা আইটেম </label>
          <select defaultValue="10">
            {['১০', '২০', '৫০', '১০০'].map((v, i) => (
              <option key={i} value={[10, 20, 50, 100][i]}>
                {v}
              </option>
            ))}
          </select>
        </div>
      </div>
      <table className="notice-table">
        <thead className="table-thead">
          <tr className="table-tr">
            {page.columns.map((c, i) => (
              <th className="table-th" key={i}>
                {c}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="table-tbody">
          {page.rows.map((r, i) => (
            <tr className="table-tr" key={i}>
              {r.map((c, j) => (
                <td className="table-td" key={j}>
                  {c.img ? (
                    <a className="table-td-icon" href={c.img} target="_blank" rel="noreferrer">
                      <img src={c.img} alt="" />
                    </a>
                  ) : c.href ? (
                    <Link to={c.href}>{c.text}</Link>
                  ) : (
                    c.text
                  )}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
      <Pagination total={page.rows.length} />
    </div>
  )
}

function OfficersPage({ page }) {
  return (
    <div className="widget content-browse-widget">
      <div className="title-bar">
        <div>
          <h2>{page.title}</h2>
        </div>
      </div>
      <div className="container-group widget-container-row">
        <div className="taxonomy-filter-container flex-one">
          <p>Designation </p>
          <p>
            <select className="taxonomy-select" defaultValue="none">
              <option value="none">(সকল)</option>
              <option>Assistant Director (Research)</option>
              <option>যুগ্ম সচিব</option>
              <option>নীরিক্ষা ও হিসাব রক্ষণ কর্মকর্তা</option>
            </select>
          </p>
        </div>
      </div>
      <div className="container-group">
        <div className="searchbar">
          <input id="search" name="search" placeholder="অনুসন্ধান" type="text" />
          <button id="search-btn">
            <i className="ph ph-magnifying-glass" />
          </button>
        </div>
        <div className="orderBy-dropdown">
          <label>ক্রম অনুসারে</label>
          <select defaultValue="-1">
            <option value="-1">ডিফল্ট</option>
            <option>শিরোনাম</option>
            <option>পদবি / পোস্ট</option>
            <option>ইমেইল</option>
            <option>ফোন (অফিস)</option>
          </select>
        </div>
        <div className="pagesize-dropdown">
          <label>পৃষ্ঠা আইটেম</label>
          <select defaultValue="250">
            {['১০', '২০', '৫০', '১০০', '২৫০'].map((v, i) => (
              <option key={i} value={[10, 20, 50, 100, 250][i]}>
                {v}
              </option>
            ))}
          </select>
        </div>
      </div>
      <div className="browse-items view-type-list grouped-view">
        <h3>(অফিসার ক্যাটাগরি উল্লেখিত নয়)</h3>
        {page.officers.map((o, i) => (
          <div className="widget employee-content-widget" key={i}>
            <div className="list-card-body">
              <div className="list-card-body-sl">{o.sl}</div>
              <div className="image-section">
                {o.photo ? <img alt="" className="list-card-image" src={o.photo} /> : null}
              </div>
              <div className="right-section">
                <table>
                  <tbody>
                    {['নাম', 'পদবি', 'অফিস', 'ইমেইল'].map((k) => (
                      <tr key={k}>
                        <td>{k}</td>
                        <td>{o[k] || ''}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="right-section">
                <table>
                  <tbody>
                    {['ফোন (অফিস)', 'ইন্টারকম', 'কক্ষ নম্বর', 'মোবাইল', 'ফ্যাক্স'].map((k) => (
                      <tr key={k}>
                        <td>{k}</td>
                        <td>{o[k] || ''}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div className="see-all-btn-block">
                  <span className="download-vcard-btn">ভিকার্ড ডাউনলোড করুন</span>
                  <span>•</span>
                  <Link className="see-all-btn" to={o.href}>
                    দেখুন
                  </Link>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
      <Pagination total={page.officers.length} />
    </div>
  )
}

export default function Page() {
  const { pathname } = useLocation()
  let decoded = pathname
  try {
    decoded = decodeURIComponent(pathname)
  } catch (e) {
    /* keep raw */
  }
  const page =
    pages[decoded] ||
    pages[pathname] ||
    pages[decoded.replace(/\/$/, '')] ||
    pages[pathname.replace(/\/$/, '')]

  if (!page) return <NotFound />

  return (
    <>
      <ShareWidget />
      {page.kind === 'table' ? (
        <TablePage page={page} />
      ) : page.kind === 'officers' ? (
        <OfficersPage page={page} />
      ) : page.kind === 'raw' ? (
        <Html html={page.html} />
      ) : (
        <ContentPage page={page} />
      )}
      <ShareWidget />
    </>
  )
}
