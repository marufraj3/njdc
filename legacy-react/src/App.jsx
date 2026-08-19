import { useEffect } from 'react'
import { Routes, Route, useLocation, useNavigate } from 'react-router-dom'
import TopBar from './components/TopBar'
import Banner from './components/Banner'
import Menu from './components/Menu'
import Sidebar from './components/Sidebar'
import Footer from './components/Footer'
import Accessibility from './components/Accessibility'
import GoToTop from './components/GoToTop'
import Home from './pages/Home'
import Page from './pages/Page'
import NotFound from './pages/NotFound'
import { site } from './lib/data'

// Rich-text content is injected as raw HTML; make internal links use the router.
function useInternalLinkRouting() {
  const navigate = useNavigate()
  useEffect(() => {
    const onClick = (e) => {
      const a = e.target.closest && e.target.closest('a')
      if (!a) return
      const href = a.getAttribute('href')
      if (!href || !href.startsWith('/') || href.startsWith('//')) return
      if (a.target === '_blank' || e.metaKey || e.ctrlKey) return
      if (/\.(pdf|jpg|jpeg|png|gif|docx?|xlsx?)$/i.test(href)) return
      e.preventDefault()
      navigate(href)
    }
    document.addEventListener('click', onClick)
    return () => document.removeEventListener('click', onClick)
  }, [navigate])
}

function ScrollToTop() {
  const { pathname } = useLocation()
  useEffect(() => {
    window.scrollTo(0, 0)
  }, [pathname])
  return null
}

export default function App() {
  const { pathname } = useLocation()
  useInternalLinkRouting()

  useEffect(() => {
    document.title =
      (pathname === '/' ? 'হোম' : 'পাতা') + ' | ' + site.officeTitle
  }, [pathname])

  return (
    <div className="container">
      <ScrollToTop />
      <div className="header">
        <div className="droppable">
          <TopBar />
          <Banner />
          <Menu />
        </div>
      </div>

      <div className="wrapper">
        <div className="body">
          <div className="droppable" id="main-content">
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="*" element={<Page />} />
            </Routes>
            <Accessibility />
            <GoToTop />
          </div>
        </div>
        <Sidebar />
      </div>

      <Footer />
    </div>
  )
}
