export default function GoToTop() {
  return (
    <div className="widget go-to-top-widget">
      <div
        className="go-to-top-float x-fab-icon"
        id="go-to-top-btn"
        title="উপরে যান"
        onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      >
        <i className="ph ph-caret-up" />
      </div>
    </div>
  )
}
