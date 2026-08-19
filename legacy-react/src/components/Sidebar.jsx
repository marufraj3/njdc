import { sidebar } from '../lib/data'
import Html from '../lib/Html'
import Link from './Link'

function BlockWidget({ w }) {
  return (
    <div className="widget block-widget">
      <div className="block-widget-container">
        {w.title ? <h3 className="block-widget-title">{w.title}</h3> : null}
        <Html className="block-widget-content" html={w.html} />
      </div>
    </div>
  )
}

function EServiceCard({ w }) {
  return (
    <div className="widget e-service-card-widget">
      <h1 className="e-service-card-header">{w.title}</h1>
      <ul className="e-service-card-body">
        {(w.items || []).map((it, i) => (
          <li className="e-service-card-list" key={i}>
            <div className="e-service-card-image" />
            <Link className="e-service-card-list-link" to={it.href}>
              {it.label}
            </Link>
          </li>
        ))}
      </ul>
      <div className="all-btn">
        <Link to={w.allHref || '#'}>সকল</Link>
      </div>
    </div>
  )
}

function LinkCard({ w }) {
  return (
    <div className="widget link-card-widget">
      <h1 className="link-card-header">{w.title}</h1>
      <ul className="link-card-body">
        {(w.items || []).map((it, i) => (
          <li className="link-card-list" key={i}>
            <div className="link-card-image" />
            <Link className="link-card-a" to={it.href}>
              {it.label}
            </Link>
          </li>
        ))}
      </ul>
      <div className="all-btn">
        <Link to="/pages/external-links">সকল</Link>
      </div>
    </div>
  )
}

export default function Sidebar() {
  return (
    <div className="right">
      <div className="droppable">
        {sidebar.map((w, i) => {
          switch (w.type) {
            case 'BlockWidget':
              return <BlockWidget w={w} key={i} />

            case 'InternalEServiceCardWidget':
              return <EServiceCard w={w} key={i} />

            case 'CentralEServiceLinkWidget':
              return (
                <div className="central-service-link-widget widget" key={i}>
                  <div className="sidebar-link-widget widget">
                    <a
                      className="sidebar-link-widget-link"
                      href={w.href}
                      target="_blank"
                      rel="noreferrer"
                    >
                      {w.title}
                    </a>
                  </div>
                </div>
              )

            case 'MyGovServiceImageLinkWidget':
              return (
                <div className="widget my-gov-service-image-link-widget" key={i}>
                  <a href="https://www.mygov.bd/" target="_blank" rel="noreferrer">
                    <img className="image" src="/assets/service_link_5.jpg" alt="mygov" />
                  </a>
                </div>
              )

            case 'OfficeDigitalServiceImageLinkWidget':
              return (
                <div className="widget office-digital-service-image-link-widget" key={i}>
                  <a
                    href="https://www.mygov.bd/serviceByOffice/?agent=np"
                    target="_blank"
                    rel="noreferrer"
                  >
                    <img className="image" src="/assets/service_link_3.gif" alt="digital service" />
                  </a>
                </div>
              )

            case 'ImportantLinkCardWidget':
              return <LinkCard w={w} key={i} />

            case 'NationalAnthemWidget':
              return (
                <div className="widget national-anthem-widget" key={i}>
                  <h6 className="national-anthem-header">জাতীয় সঙ্গীত</h6>
                  <div className="national-anthem-audio-block">
                    <audio className="national-anthem-audio" controls style={{ width: '100%' }}>
                      <source
                        src="https://objectstorage.ap-dcc-gazipur-1.oraclecloud15.com/n/axvjbnqprylg/b/V2Ministry/o/general-space/bd_national_anthem.mp3"
                        type="audio/mp3"
                      />
                    </audio>
                  </div>
                </div>
              )

            case 'BdWorkersTrustBoardImageLinkWidget':
              return (
                <div className="widget bd-workers-trust-board-image-link-widget" key={i}>
                  <a href="https://bkkb.portal.gov.bd/" target="_blank" rel="noreferrer">
                    <img className="image" src="/assets/service_link_4.png" alt="bkkb" />
                  </a>
                </div>
              )

            case 'SocialMediaCardWidget':
              return (
                <div className="widget social-media-widget" key={i}>
                  <h1 className="social-media-header">{w.title}</h1>
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
              )

            case 'OfficeAttachmentApplicationFormWidget':
              return (
                <div className="widget office-attachment-application-form-widget" key={i}>
                  <h1 className="office-attachment-application-form-widget-header">
                    <a
                      href="https://pms.portal.gov.bd/office/outauth_new_office"
                      target="_blank"
                      rel="noreferrer"
                    >
                      সরকারি অফিসের নতুন ওয়েবসাইটের আবেদন
                    </a>
                  </h1>
                </div>
              )

            case 'EmergencyHotlineListCardWidget':
              return <Html key={i} html={w.html} />

            default:
              return <Html key={i} html={w.html} />
          }
        })}
      </div>
    </div>
  )
}
