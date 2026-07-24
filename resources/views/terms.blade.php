@extends('layouts.classic')

@section('wfPage', 'legal-terms')
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
              These Terms of Use (“Terms”) govern your access to and use of the website
              <a href="https://deluxewindows.com">deluxewindows.com</a> (the “Site”) operated by
              Deluxe Windows, Inc. (“Deluxe Windows,” “we,” “us,” or “our”). By accessing or using the Site,
              you agree to these Terms. If you do not agree, do not use the Site.
            </p>

            <h2 id="eligibility">1. Eligibility</h2>
            <p>
              You must be at least 18 years old and able to form a binding contract under U.S. law to use the
              Site or submit requests for estimates or services. By using the Site, you represent that you meet
              these requirements.
            </p>

            <h2 id="services">2. Our Services and Site Content</h2>
            <p>
              Deluxe Windows provides window and door products, replacement, and related installation services,
              primarily in the San Francisco Bay Area of California. The Site is for general information and
              lead generation. Product descriptions, pricing, promotions, financing information, timelines, and
              availability may change without notice and may vary by location, product, and project conditions.
            </p>
            <p>
              Online prices and promotional offers are illustrative unless confirmed in a written estimate or
              contract signed by Deluxe Windows. Measurements, structural conditions, permits, and product
              selections can affect final pricing.
            </p>

            <h2 id="estimates">3. Estimates, Quotes, and Contracts</h2>
            <p>
              Submitting a contact form or receiving an estimate does not create a binding construction or sales
              contract. A binding agreement is formed only when you and Deluxe Windows execute a written
              contract (or other writing we designate) that states the scope, price, and terms of the work.
              Financing, if offered, is provided by third-party lenders and is subject to credit approval and
              separate lender terms.
            </p>

            <h2 id="acceptable-use">4. Acceptable Use</h2>
            <p>You agree not to:</p>
            <ul>
              <li>Use the Site for any unlawful purpose or in violation of these Terms</li>
              <li>Submit false, misleading, or fraudulent information</li>
              <li>Attempt to gain unauthorized access to the Site, servers, or related systems</li>
              <li>Interfere with or disrupt the Site, including through malware, scraping that overloads systems, or automated abuse</li>
              <li>Copy, modify, or redistribute Site content except as allowed by these Terms or applicable law</li>
            </ul>

            <h2 id="ip">5. Intellectual Property</h2>
            <p>
              The Site and its content—including text, graphics, logos, images, and layout—are owned by
              Deluxe Windows or its licensors and are protected by U.S. and international intellectual property
              laws. You may view and print content for personal, non-commercial use related to evaluating our
              services. Any other use requires our prior written permission.
            </p>

            <h2 id="user-content">6. User Submissions</h2>
            <p>
              If you submit information or materials through the Site (for example, project details or messages),
              you grant Deluxe Windows a non-exclusive, worldwide, royalty-free license to use that information
              to respond to you and operate our business. You represent that you have the right to provide the
              information and that it does not violate any law or third-party rights.
            </p>

            <h2 id="third-parties">7. Third-Party Links and Tools</h2>
            <p>
              The Site may link to third-party websites, widgets, financing applications, review platforms, or
              tools. We are not responsible for third-party content, policies, or practices. Your use of
              third-party services is at your own risk and subject to their terms.
            </p>

            <h2 id="disclaimers">8. Disclaimers</h2>
            <p>
              THE SITE AND ALL CONTENT ARE PROVIDED “AS IS” AND “AS AVAILABLE.” TO THE MAXIMUM EXTENT PERMITTED
              BY U.S. LAW, DELUXE WINDOWS DISCLAIMS ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING IMPLIED
              WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT. WE DO NOT
              WARRANT THAT THE SITE WILL BE UNINTERRUPTED, ERROR-FREE, OR FREE OF HARMFUL COMPONENTS.
            </p>
            <p>
              Nothing on the Site constitutes professional engineering, architectural, or legal advice. Always
              rely on a written contract and applicable building codes for project decisions.
            </p>

            <h2 id="liability">9. Limitation of Liability</h2>
            <p>
              TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, DELUXE WINDOWS AND ITS OFFICERS, EMPLOYEES,
              AND AGENTS WILL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, EXEMPLARY, OR
              PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS, DATA, OR BUSINESS, ARISING OUT OF OR RELATED TO YOUR USE
              OF THE SITE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
            </p>
            <p>
              OUR TOTAL LIABILITY FOR CLAIMS ARISING OUT OF OR RELATED TO THE SITE WILL NOT EXCEED ONE HUNDRED
              U.S. DOLLARS (US $100), EXCEPT WHERE LIABILITY CANNOT BE LIMITED UNDER APPLICABLE LAW. THESE
              LIMITATIONS DO NOT LIMIT LIABILITY ARISING UNDER A SEPARATE WRITTEN SERVICE CONTRACT FOR
              INSTALLATION OR PRODUCT WORK, WHICH IS GOVERNED BY THAT CONTRACT.
            </p>

            <h2 id="indemnity">10. Indemnification</h2>
            <p>
              You agree to defend, indemnify, and hold harmless Deluxe Windows and its officers, employees, and
              agents from claims, damages, losses, and expenses (including reasonable attorneys’ fees) arising
              out of your misuse of the Site, your violation of these Terms, or your violation of any law or
              third-party right.
            </p>

            <h2 id="privacy">11. Privacy</h2>
            <p>
              Our collection and use of personal information are described in our
              <a href="/privacy-policy">Privacy Policy</a>, which is incorporated into these Terms by reference.
            </p>

            <h2 id="governing-law">12. Governing Law and Disputes</h2>
            <p>
              These Terms are governed by the laws of the State of California, without regard to conflict-of-law
              rules. Except where prohibited by law, you agree that exclusive venue for disputes arising out of
              or relating to the Site or these Terms will be the state or federal courts located in California,
              and you consent to personal jurisdiction there.
            </p>
            <p>
              If you have a dispute related to contracted installation or product work, the dispute-resolution
              terms in your written contract with Deluxe Windows control for that engagement.
            </p>

            <h2 id="changes">13. Changes</h2>
            <p>
              We may update these Terms at any time by posting a revised version on the Site with an updated
              effective date. Your continued use of the Site after changes become effective constitutes
              acceptance of the revised Terms.
            </p>

            <h2 id="misc">14. Miscellaneous</h2>
            <p>
              If any provision of these Terms is found unenforceable, the remaining provisions will remain in
              effect. Our failure to enforce a provision is not a waiver. These Terms are the entire agreement
              between you and Deluxe Windows regarding the Site and supersede prior agreements on that subject.
            </p>

            <h2 id="contact">15. Contact</h2>
            <ul>
              <li><strong>Deluxe Windows, Inc.</strong></li>
              <li>Email: <a href="mailto:info@deluxewindows.com">info@deluxewindows.com</a></li>
              <li>Phone: <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a></li>
              <li>Website: <a href="/contacts">Contact form</a></li>
            </ul>
          </div>
        </div>
      </section>
@endsection
