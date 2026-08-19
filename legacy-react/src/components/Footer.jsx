import Link from './Link'

export default function Footer() {
  return (
    <div className="footer">
      <div className="droppable">
        <footer className="widget footer-widget">
          <div className="footer-widget-image" />
          <div className="footer-disclaimer">
            এই ওয়েবসাইটে প্রকাশিত সকল তথ্য সংশ্লিষ্ট দপ্তর কর্তৃক নিয়মিত হালনাগাদ করা হয়। তথ্যের
            যথার্থতা, নির্ভুলতা ও নির্ভরযোগ্যতা নিশ্চিত করতে সংশ্লিষ্ট দপ্তর সর্বদা সচেষ্ট।
          </div>
          <div className="footer-body">
            <div>
              <ul className="left-ul">
                <li className="left-ul-list">
                  <Link className="footer-link" to="/forms/form/feedback-forms">
                    ফিডব্যাক ফরম
                  </Link>
                </li>
                <li className="left-ul-list">
                  <a className="footer-link" href="#" onClick={(e) => e.preventDefault()}>
                    ব্যবহার শর্তাবলী
                  </a>
                </li>
                <li className="left-ul-list">
                  <a className="footer-link" href="#" onClick={(e) => e.preventDefault()}>
                    সার্বিক ব্যবস্থাপনায়
                  </a>
                </li>
                <li className="left-ul-list">
                  <Link className="footer-link" to="/views/sitemap">
                    সাইটম্যাপ
                  </Link>
                </li>
              </ul>
              <div className="site-update-block">
                <p>সাইটটি শেষ হাল-নাগাদ করা হয়েছে: মঙ্গলবার, ৪ আগস্ট, ২০২৬ এ ১৮:৪৬:৩৯</p>
              </div>
            </div>
            <div className="text-xs">
              <p>পরিকল্পনা এবং বাস্তবায়ন: মন্ত্রিপরিষদ বিভাগ, এটুআই, বিসিসি, ডিওআইসিটি ও বেসিস।</p>
              <div className="technical-support-block">
                <p className="technical-support-text">কারিগরি সহায়তা</p>
                <img
                  alt="np-logo-set"
                  className="technical-support-image"
                  src="/assets/technical-support.svg"
                />
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
  )
}
