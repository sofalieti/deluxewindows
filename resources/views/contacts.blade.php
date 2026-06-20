@extends('layouts.classic')

@section('wfPage', '6841ddf8ace3d9d9facb1638')
@section('title', 'Contact Deluxe Windows | Bay Area Window Experts')
@section('metaDescription', 'Get in touch with Deluxe Windows in San Francisco. Call, email, or request a free quote for window and door installation across the Bay Area. We respond fast.')
@section('ogImage', 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da952cef202b8dda5788c_Meta%20cover-2.jpg')

@section('head')
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HomeAndConstructionBusiness",
      "name": "Deluxe Windows",
      "url": "https://www.deluxewindows.com",
      "telephone": "{{ site_phone_tel() }}",
      "description": "Premium window and door replacement for San Francisco Bay Area homes. 30+ years, 100% employee owned.",
      "priceRange": "$$",
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "231",
        "bestRating": "5"
      },
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
          "opens": "08:00",
          "closes": "18:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Saturday",
          "opens": "09:00",
          "closes": "15:00"
        }
      ],
      "areaServed": {
        "@type": "GeoCircle",
        "geoMidpoint": {
          "@type": "GeoCoordinates",
          "latitude": 37.5630,
          "longitude": -122.0329
        },
        "geoRadius": "100000"
      }
    }
    </script>
@endsection

@section('content')
      @include('partials.contacts-webflow-section')

      @include('partials.faq', [
        'sectionExtraClass' => ' top-none',
        'faqFormHref' => '#wf-form-Contact-V1-Form',
      ])
@endsection
