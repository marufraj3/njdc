import { Link } from 'react-router-dom'
import { site } from '../lib/data'

export default function Banner() {
  return (
    <div className="widget banner-slider-image-widget">
      <div className="home-carousel">
        <div className="slider images">
          <a href={site.heroImage} target="_blank" rel="noreferrer">
            <img className="slider-image" src={site.heroImage} alt={site.heroAlt} />
          </a>
          <div className="slider-overlay widget-container-row">
            <div className="slider-left container-col-4">
              <Link to="/">
                <img className="office-logo" src="/assets/logo.png" alt="Office Logo" />
              </Link>
              <div className="office-left-section">
                <h1>
                  <Link style={{ textDecoration: 'none' }} to="/" className="office-title">
                    {site.officeTitle}
                  </Link>
                </h1>
                <p className="office-subtitle">{site.officeSubtitle}</p>
              </div>
            </div>
            <div className="slider-controls container-col-4">
              <button className="nav-btn slider-previous" aria-label="previous">
                <svg width="8" height="13" viewBox="0 0 8 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M6.70508 11.7868L1.79976 6.88151L6.70508 1.9762"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                </svg>
              </button>
              <button className="nav-btn slider-play" aria-label="play">
                <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    fillRule="evenodd"
                    clipRule="evenodd"
                    d="M10.6445 20.8813C13.2967 20.8813 15.8402 19.8278 17.7156 17.9524C19.591 16.0771 20.6445 13.5335 20.6445 10.8813C20.6445 8.22918 19.591 5.68564 17.7156 3.81028C15.8402 1.93492 13.2967 0.881348 10.6445 0.881348C7.99237 0.881348 5.44883 1.93492 3.57346 3.81028C1.6981 5.68564 0.644531 8.22918 0.644531 10.8813C0.644531 13.5335 1.6981 16.0771 3.57346 17.9524C5.44883 19.8278 7.99237 20.8813 10.6445 20.8813ZM10.0883 7.34135C9.90003 7.21575 9.68121 7.14361 9.45518 7.13263C9.22914 7.12165 9.00436 7.17224 8.80482 7.27901C8.60529 7.38577 8.43848 7.5447 8.32219 7.73884C8.2059 7.93298 8.1445 8.15504 8.14453 8.38135V13.3813C8.1445 13.6077 8.2059 13.8297 8.32219 14.0239C8.43848 14.218 8.60529 14.3769 8.80482 14.4837C9.00436 14.5905 9.22914 14.641 9.45518 14.6301C9.68121 14.6191 9.90003 14.5469 10.0883 14.4213L13.8383 11.9213C14.0095 11.8072 14.1498 11.6525 14.2469 11.4711C14.344 11.2897 14.3948 11.0871 14.3948 10.8813C14.3948 10.6756 14.344 10.473 14.2469 10.2916C14.1498 10.1102 14.0095 9.9555 13.8383 9.84135L10.0883 7.34135Z"
                    fill="currentColor"
                  />
                </svg>
              </button>
              <button className="nav-btn slider-next" aria-label="next">
                <svg width="8" height="13" viewBox="0 0 8 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M1.58447 11.7868L6.48979 6.88151L1.58447 1.9762"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
