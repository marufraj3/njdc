import { useEffect, useRef, useState } from 'react'
import { home } from '../lib/data'
import Html from '../lib/Html'
import Link from '../components/Link'

function NoticeNewsCard() {
  return (
    <section className="widget notice-news-card-widget">
      <div className="notice-card">
        <p className="notice-title">
          <i className="ph ph-file-text" /> নোটিশ বোর্ড
        </p>
        <ul className="notice-unordered-list">
          {home.notices.map((n, i) => (
            <li className="notice-content-list" key={i}>
              <Link className="notice-link" to={n.href}>
                <div className="notice-content-icon">
                  <i className="dot" />
                </div>
                <div className="notice-text-wrap">
                  <p className="notice-text" title={n.title}>
                    {n.title}
                  </p>
                  <p className="notice-text">
                    <span className="notice-tag">
                      <i className="ph ph-calendar-dots" /> {n.date}
                    </span>
                    <strong className="notice-tag" />
                    <strong className="notice-tag">{n.tag}</strong>
                  </p>
                </div>
                <div className="notice-content-icon">
                  <i className="ph ph-caret-right" />
                </div>
              </Link>
            </li>
          ))}
        </ul>
      </div>
      <div className="all-btn">
        <Link to="/pages/notices">
          সকল নোটিশ দেখুন <i className="ph ph-arrow-right" />
        </Link>
      </div>
      <div className="news-card">
        <section className="widget news-card-widget">
          <div className="news-card-widget-scroll-container">
            <div className="news-card-widget-news-title">খবর</div>
            <NewsTicker />
            <div className="all-btn">
              <Link to="/pages/news">সকল</Link>
            </div>
          </div>
        </section>
      </div>
    </section>
  )
}

function NewsTicker() {
  const items = home.news
  const [i, setI] = useState(0)
  useEffect(() => {
    if (items.length < 2) return
    const t = setInterval(() => setI((v) => (v + 1) % items.length), 4000)
    return () => clearInterval(t)
  }, [items.length])
  return (
    <div className="news-card-widget-ticker">
      {items.map((n, k) => (
        <Link
          key={k}
          to={n.href}
          className={'new-content scroll-text' + (k === i ? ' ticker-active' : '')}
        >
          {n.title}
        </Link>
      ))}
    </div>
  )
}

function PhotoSlider() {
  const photos = home.photos
  const [idx, setIdx] = useState(0)
  const timer = useRef(null)

  useEffect(() => {
    timer.current = setInterval(() => setIdx((v) => (v + 1) % photos.length), 5000)
    return () => clearInterval(timer.current)
  }, [photos.length])

  const go = (d) => setIdx((v) => (v + d + photos.length) % photos.length)

  return (
    <section className="widget home-photo-slider-widget">
      <div className="home-photo-slider-widget-carousel">
        {photos.map((p, i) => (
          <div
            key={i}
            className="home-photo-slider-widget-slider home-photo-slider-widget-images"
            style={{
              backgroundImage: `url(${p.src})`,
              display: i === idx ? 'block' : 'none',
            }}
          >
            <img className="home-photo-slider-widget-slider-image" src={p.src} alt={p.caption} />
            <div className="photo-slider-caption">{p.caption}</div>
          </div>
        ))}
        <a className="home-photo-slider-widget-slider-previous" onClick={() => go(-1)}>
          ❮
        </a>
        <a className="home-photo-slider-widget-slider-next" onClick={() => go(1)}>
          ❯
        </a>
      </div>
      <br />
      <div className="home-photo-slider-widget-block">
        <div className="home-photo-slider-widget-navigator">
          {photos.map((p, i) => (
            <img
              key={i}
              className={
                'home-photo-slider-widget-slider-navigation-img' + (i === idx ? ' active' : '')
              }
              src={p.src}
              alt={p.caption}
              onClick={() => setIdx(i)}
            />
          ))}
        </div>
      </div>
    </section>
  )
}

function ServiceBoxes() {
  const [expanded, setExpanded] = useState(false)
  const sb = home.serviceBoxes
  const boxes = expanded ? sb.boxes : sb.boxes.slice(0, 8)

  return (
    <section className="widget service-box-expandable-stack-widget">
      <section className="widget service-box-stack-widget widget-container-row">
        <div className="service-box-stack-widget-header">
          <p className="service-box-stack-widget-title">{sb.title}</p>
          <Link className="service-box-stack-widget-link" to={sb.allHref}>
            সব দেখুন
          </Link>
        </div>
        {boxes.map((b, i) => (
          <div className="container-col-6" key={i}>
            <div className="widget service-box-widget">
              <h1 className="service-box-title" style={{ color: 'black' }}>
                {b.title}
              </h1>
              <div className="service-box-grid">
                <div className="service-box-col-span-4 service-box-img-container">
                  {b.image ? <img alt={b.title} src={b.image} /> : null}
                </div>
                <div className="service-box-col-span-8">
                  <ul className="service-box-list">
                    {b.links.map((l, j) => (
                      <li className="service-box-list-item" key={j}>
                        <div className="service-box-bullet" />
                        <Link className="service-box-list-link" to={l.href} title={l.label}>
                          {l.label}
                        </Link>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            </div>
          </div>
        ))}
      </section>
      {sb.boxes.length > 8 && (
        <div className="all-btn-wrapper">
          <label className="all-btn" onClick={() => setExpanded((v) => !v)}>
            {expanded ? (
              <span>
                সংক্ষিপ্ত <i className="ph ph-caret-up" />
              </span>
            ) : (
              <span>
                সকল সেবাসমূহ দেখুন <i className="ph ph-caret-down" />
              </span>
            )}
          </label>
        </div>
      )}
    </section>
  )
}

function HomeBlocks() {
  return (home.blocks || []).map((b, i) => (
    <div className="widget block-widget" key={i}>
      <div className="block-widget-container">
        {b.title ? <h3 className="block-widget-title">{b.title}</h3> : null}
        <Html className="block-widget-content" html={b.html} />
      </div>
    </div>
  ))
}

function GetInTouch() {
  const g = home.getInTouch
  if (!g) return null
  return (
    <div className="get-in-touch-widget widget">
      <div className="get-in-touch-container">
        <div className="get-in-touch-row">
          <div className="get-in-touch-col content-col">
            <h2 className="get-in-touch-title">{g.title}</h2>
            <div className="office-info">
              <h3 className="office-name">{g.office}</h3>
              <ul className="contact-list">
                {g.items.map((it, i) => (
                  <li key={i}>
                    <i className={it.icon} />
                    <b className="label">{it.label} </b>
                    <span>{it.value}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div className="social-media-container">
              <div className="widget social-link-media-widget">
                <a
                  href="https://www.facebook.com/jdpcbd/"
                  style={{ textDecoration: 'none' }}
                  title="Facebook"
                  target="_blank"
                  rel="noreferrer"
                >
                  <i
                    className="ph-fill ph-facebook-logo media-icon social-link-media-widget-facebook-icon"
                    style={{ color: '#3b5998' }}
                  />
                </a>
              </div>
            </div>
          </div>
          <div className="get-in-touch-col map-col">
            <div className="map-container">
              <div className="office-location-widget widget">
                <div className="office-location-widget-container">
                  <h2 className="office-location-widget-title">{g.mapTitle}</h2>
                  <div className="office-location-widget-iframe-container">
                    <iframe
                      className="office-location-widget-iframe"
                      loading="lazy"
                      referrerPolicy="no-referrer-when-downgrade"
                      src={g.mapSrc}
                      title="map"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default function Home() {
  return (
    <>
      <NoticeNewsCard />
      <PhotoSlider />
      <ServiceBoxes />
      <HomeBlocks />
      <GetInTouch />
    </>
  )
}
