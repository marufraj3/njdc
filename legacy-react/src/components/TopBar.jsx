import { site } from '../lib/data'

const OFFICE_TYPES = [
  'অফিসের ধরণ নির্বাচন করুন',
  'স্বায়ত্তশাসিত',
  'মন্ত্রণালয়',
  'মন্ত্রণালয় বিভাগ',
  'অধিদপ্তর',
  'কর্পোরেশন',
  'কমিশন',
  'কোম্পানি',
  'কর্তৃপক্ষ/অথরিটি',
  'এজেন্সী',
  'ব্যাংক / বীমা / আর্থিক প্রতিষ্ঠান',
  'বিভাগীয় পোর্টাল',
  'জেলা পোর্টাল',
  'পৌরসভা পোর্টাল',
  'উপজেলা পোর্টাল',
  'ইউনিয়ন পোর্টাল',
  'রেজাল্ট',
  'প্রকল্প',
  'বিদেশী দূতাবাস/মিশন',
  'জেলা পরিষদ পোর্টাল',
  'সিটি কর্পোরেশন পোর্টাল',
  'প্রশিক্ষণ',
]

export default function TopBar() {
  return (
    <section className="widget header-widget-section">
      <div className="header-left-section">
        <a className="header-title" href={site.portalUrl} title={site.portalTitle}>
          {site.portalTitle}
        </a>
      </div>

      <div className="header-left-section">
        <div className="widget header-dropdown custom-items-center top-menu office-findthree-widget office-findv2-widget">
          <div className="office-group">
            <select defaultValue="অধিদপ্তর" title="অধিদপ্তর">
              {OFFICE_TYPES.map((t) => (
                <option key={t} value={t}>
                  {t}
                </option>
              ))}
            </select>
            <div className="dynamic-dropdowns" />
            <button disabled>দেখুন</button>
          </div>
        </div>
      </div>

      <div className="global-searchbar custom-items-center">
        <div className="widget global-search-widget">
          <input className="input-search" name="key" placeholder="এখানে খুঁজুন..." />
          <button className="btn-search" type="button">
            অনুসন্ধান
          </button>
        </div>
        <div className="widget language-switcher-widget">
          <button className="btn-lang-change" data-type="bn" type="button">
            English
          </button>
        </div>
      </div>
    </section>
  )
}
