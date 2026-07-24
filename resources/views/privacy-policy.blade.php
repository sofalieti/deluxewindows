@extends('layouts.classic')

@section('wfPage', 'legal-privacy-policy')
@section('metadataFaqRendered', '1')

@section('head')
<link rel="stylesheet" href="{{ asset('webflow-overrides/legal-pages.css') }}" />
@endsection

@section('content')
      <section class="section-card-wrapper top page-intro-hero">
        <div class="section-card hero-card---120px-page">
          <div class="w-layout-blockcontainer container-default w-container">
            <div class="inner-container _850px center">
              <div class="center-content">
                <h1 class="display-10 mid text-light">{{ $pageMetadata->h1 }}</h1>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="card template-pages---text-card legal-page">
            <p class="legal-page__updated"><strong>Effective date:</strong> July 24, 2026</p>
            <p class="legal-page__intro">
              This Privacy Policy describes how Deluxe Windows, Inc. (“Deluxe Windows,” “we,” “us,” or “our”)
              collects, uses, discloses, and protects personal information when you visit
              <a href="https://deluxewindows.com">deluxewindows.com</a> (the “Site”), request an estimate,
              call or email us, or otherwise interact with our services in the United States.
            </p>
            <p>
              We serve homeowners primarily in California’s San Francisco Bay Area. This Policy is designed
              to align with applicable U.S. privacy laws, including the California Consumer Privacy Act
              (CCPA), as amended by the California Privacy Rights Act (CPRA).
            </p>

            <h2 id="info-we-collect">1. Information We Collect</h2>
            <h3>Information you provide</h3>
            <p>We may collect information you voluntarily submit, including:</p>
            <ul>
              <li>Name, email address, phone number, and city or service area</li>
              <li>Project details and messages you include in contact or quote forms</li>
              <li>Records of calls, emails, and appointment requests</li>
            </ul>

            <h3>Information collected automatically</h3>
            <p>When you use the Site, we and our service providers may automatically collect:</p>
            <ul>
              <li>IP address, browser type, device information, and general location derived from IP</li>
              <li>Pages viewed, referring URLs, timestamps, and interaction data</li>
              <li>Cookie identifiers and similar technologies</li>
              <li>Advertising and analytics identifiers (for example, Google Analytics / Google Ads parameters such as gclid, and similar campaign parameters)</li>
            </ul>

            <h2 id="how-we-use">2. How We Use Information</h2>
            <p>We use personal information to:</p>
            <ul>
              <li>Respond to inquiries and provide estimates, sales, and installation services</li>
              <li>Schedule consultations and communicate about your project</li>
              <li>Operate, maintain, secure, and improve the Site</li>
              <li>Measure marketing performance and understand how visitors use the Site</li>
              <li>Comply with law, enforce our terms, and protect our rights and customers</li>
            </ul>

            <h2 id="cookies">3. Cookies, Analytics, and Advertising</h2>
            <p>
              We use cookies and similar technologies for essential site functions, analytics, and advertising
              measurement. Third parties such as Google may set or read cookies and receive limited data about
              your use of the Site. You can control cookies through your browser settings. Blocking some cookies
              may affect Site functionality.
            </p>
            <p>
              Google may use data collected on our Site for advertising purposes as described in Google’s
              policies. You can learn more and manage ad preferences at
              <a href="https://adssettings.google.com" target="_blank" rel="noopener">adssettings.google.com</a>
              and review Google’s privacy practices at
              <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a>.
            </p>

            <h2 id="sharing">4. How We Share Information</h2>
            <p>We may share personal information with:</p>
            <ul>
              <li><strong>Service providers</strong> who help us operate our business (hosting, email delivery, analytics, CRM/lead tools, phone systems), under contractual confidentiality and use restrictions</li>
              <li><strong>Financing or product partners</strong> when you request related services and it is necessary to fulfill your request</li>
              <li><strong>Professional advisors</strong> and authorities when required by law or to protect legal rights</li>
              <li><strong>Business transfers</strong> in connection with a merger, acquisition, financing, or sale of assets</li>
            </ul>
            <p>
              We do not sell personal information for money. Under California law, some advertising or analytics
              disclosures may be considered a “sale” or “sharing” of personal information for cross-context
              behavioral advertising. See Section 6 for your choices.
            </p>

            <h2 id="retention">5. Retention</h2>
            <p>
              We retain personal information only as long as reasonably necessary for the purposes described in
              this Policy, including to provide services, maintain business and tax records, resolve disputes,
              and meet legal obligations. Retention periods vary by record type and legal requirements.
            </p>

            <h2 id="california">6. California Privacy Rights (CCPA/CPRA)</h2>
            <p>If you are a California resident, you may have the right to:</p>
            <ul>
              <li>Know what personal information we collect, use, disclose, sell, or share</li>
              <li>Access and receive a copy of your personal information</li>
              <li>Request deletion of personal information, subject to legal exceptions</li>
              <li>Correct inaccurate personal information</li>
              <li>Opt out of the sale or sharing of personal information</li>
              <li>Limit use and disclosure of sensitive personal information, where applicable</li>
              <li>Not be discriminated against for exercising these rights</li>
            </ul>
            <p>
              Categories of personal information we may collect include identifiers (name, email, phone, IP),
              commercial information (project interest), internet/electronic activity, and geolocation
              (approximate). We collect this information from you and automatically from your device/browser.
            </p>
            <p>
              To exercise rights, contact us using the details in Section 9. We will verify your request as
              required by law. You may use an authorized agent subject to verification requirements.
            </p>
            <p>
              <strong>Do Not Sell or Share My Personal Information / Limit Ad Tracking:</strong> You may request
              to opt out by emailing
              <a href="mailto:info@deluxewindows.com">info@deluxewindows.com</a>
              with the subject line “California Privacy Request,” or by calling
              <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a>.
              You may also use browser-based Global Privacy Control (GPC) signals where legally recognized;
              we will treat a valid GPC signal as an opt-out of sale/sharing for that browser where required.
            </p>

            <h2 id="other-states">7. Other U.S. State Privacy Rights</h2>
            <p>
              Residents of certain other U.S. states may have similar rights to access, delete, correct, or
              opt out of targeted advertising or sales of personal data under applicable state law. To submit a
              request, contact us as described below. We will process requests in accordance with the law that
              applies to you.
            </p>

            <h2 id="security">8. Security</h2>
            <p>
              We use reasonable administrative, technical, and physical safeguards designed to protect personal
              information. No method of transmission or storage is completely secure, and we cannot guarantee
              absolute security.
            </p>

            <h2 id="children">9. Children’s Privacy</h2>
            <p>
              The Site is not directed to children under 16, and we do not knowingly collect personal
              information from children under 16. If you believe we have collected such information, contact us
              and we will take appropriate steps to delete it.
            </p>

            <h2 id="contact">10. Contact Us</h2>
            <p>For privacy questions or requests:</p>
            <ul>
              <li><strong>Deluxe Windows, Inc.</strong></li>
              <li>Email: <a href="mailto:info@deluxewindows.com">info@deluxewindows.com</a></li>
              <li>Phone: <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a></li>
              <li>Website: <a href="/contacts">Contact form</a></li>
            </ul>

            <h2 id="changes">11. Changes to This Policy</h2>
            <p>
              We may update this Privacy Policy from time to time. The “Effective date” above will be revised
              when changes are posted. Continued use of the Site after an update means you acknowledge the
              revised Policy.
            </p>
          </div>
        </div>
      </section>
@endsection
