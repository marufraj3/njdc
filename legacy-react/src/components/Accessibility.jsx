import { useEffect, useState } from 'react'

const TOGGLES = [
  ['monochrome', 'মনোক্রোম'],
  ['inverted', 'ইনভার্ট'],
  ['bigCursor', 'বড় কার্সর'],
  ['highlightLinks', 'লিঙ্ক হাইলাইট'],
  ['highlightHeadings', 'শিরোনাম হাইলাইট'],
  ['readingGuideCheckbox', 'পড়ার গাইড'],
]

export default function Accessibility() {
  const [open, setOpen] = useState(false)
  const [font, setFont] = useState(100)
  const [flags, setFlags] = useState({})

  useEffect(() => {
    const b = document.body
    b.style.fontSize = font + '%'
    b.classList.toggle('monochrome', !!flags.monochrome)
    b.classList.toggle('inverted', !!flags.inverted)
    b.classList.toggle('bigCursor', !!flags.bigCursor)
    b.classList.toggle('highlightLinks', !!flags.highlightLinks)
    b.classList.toggle('highlightHeadings', !!flags.highlightHeadings)
  }, [font, flags])

  const reset = () => {
    setFont(100)
    setFlags({})
  }

  return (
    <div className="widget accessibility-widget">
      <div
        className="accessibility-float fab-icon"
        id="accessibility-btn"
        title="এক্সেসিবিলিটি"
        onClick={() => setOpen((v) => !v)}
      >
        <i className="ph ph-wheelchair-motion" />
      </div>
      <div
        className={'accessibility-card' + (open ? ' show' : '')}
        id="accessibility-card"
        style={{ display: open ? 'block' : undefined }}
      >
        <h3 id="accessibility-card-title" tabIndex={0}>
          এক্সেসিবিলিটি
        </h3>
        <div id="accessibility-close" tabIndex={0} title="Close" onClick={() => setOpen(false)}>
          <i className="ph ph-x-circle" />
        </div>
        <div className="item">
          <button onClick={() => setFont((f) => Math.min(f + 10, 160))}>ফন্ট বৃদ্ধি</button>
          <button onClick={() => setFont((f) => Math.max(f - 10, 70))}>ফন্ট হ্রাস</button>
        </div>
        {TOGGLES.map(([id, label]) => (
          <div className="item" key={id}>
            <input
              id={id}
              title={label}
              type="checkbox"
              checked={!!flags[id]}
              onChange={(e) => setFlags((f) => ({ ...f, [id]: e.target.checked }))}
            />
            <label htmlFor={id}>{label}</label>
          </div>
        ))}
        <div className="item">
          <button className="accessibility-reset" onClick={reset}>
            রিসেট
          </button>
        </div>
        <a
          className="screen-reader"
          href="https://www.nvaccess.org/files/nvda/releases/2020.4/nvda_2020.4.exe"
          rel="noreferrer"
          target="_blank"
        >
          স্ক্রিন রিডার ডাউনলোড করুন
        </a>
      </div>
      <div className="tab-menu">
        <a className="skip-link" href="#main-content">
          কন্টেন্টে চলে যান
        </a>
        <a className="skip-link menu-href" href="#accessibility-card-title">
          এক্সেসিবিলিটি মেনুতে যান
        </a>
      </div>
    </div>
  )
}
