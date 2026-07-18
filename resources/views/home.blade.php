@extends('layouts.classic')

@section('wfPage', '6841df5688ca2f74fd53ec90')
@section('htmlClass', 'wf-loading w-mod-js wf-exo-n1-loading wf-exo-i1-loading wf-exo-n2-loading wf-exo-i2-loading wf-exo-n3-loading wf-exo-i3-loading wf-exo-n4-loading wf-exo-i4-loading wf-exo-n5-loading wf-exo-i5-loading wf-exo-n6-loading wf-exo-i6-loading wf-exo-n7-loading wf-exo-i7-loading wf-exo-n8-loading wf-exo-i8-loading wf-exo-n9-loading wf-exo-i9-loading w-mod-ix')
@section('bodyClass', 'body-18 height-auto')

@section('head')
    <link
      rel="preload"
      as="image"
      href="{{ thumbnail_url('/webflow-assets/images/69ce36fd76a6aaff9c68df7e_01.webp', 'hero_mobile') }}"
      imagesrcset="{{ thumbnail_url('/webflow-assets/images/69ce36fd76a6aaff9c68df7e_01.webp', 'hero_mobile') }} 768w, {{ thumbnail_url('/webflow-assets/images/69ce36fd76a6aaff9c68df7e_01.webp', 'hero_bg') }} 1920w"
      imagesizes="100vw"
      fetchpriority="high"
    />
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      .wf-force-outline-none[tabindex="-1"]:focus {
        outline: none;
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      .w-webflow-badge {
        display: none !important;
      }

      /* === Layout spacing fixes === */
      /* Remove extra top padding on Doors section */
      .section.pd-top-80px { padding-top: 0 !important; }

      /* Remove grey gap above For Professionals section (after Google Maps) */
      .section.top-none { margin-top: 0 !important; }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      div.eapps-widget {
        position: relative;
      }

      div.eapps-widget.eapps-widget-show-toolbar::before {
        position: absolute;
        content: "";
        display: block;
        inset: 0px;
        pointer-events: none;
        border: 1px solid transparent;
        transition: border 0.3s;
        z-index: 1;
      }

      .eapps-widget-toolbar {
        position: absolute;
        top: -32px;
        left: 0px;
        right: 0px;
        display: block;
        z-index: 99999;
        padding-bottom: 4px;
        transition: 0.3s;
        pointer-events: none;
        opacity: 0;
      }

      .eapps-widget:hover .eapps-widget-toolbar {
        opacity: 1;
        pointer-events: auto;
      }

      .eapps-widget-toolbar a {
        text-decoration: none;
        box-shadow: none !important;
      }

      .eapps-widget-toolbar-panel {
        border-radius: 6px;
        background-color: rgb(34, 34, 34);
        color: rgb(255, 255, 255);
        display: inline-flex;
        align-items: center;
        top: 0px;
        position: relative;
        transition: 0.3s;
        opacity: 0;
        overflow: hidden;
        backface-visibility: hidden;
        box-shadow: rgba(255, 255, 255, 0.2) 0px 0px 0px 1px;
        height: 28px;
      }

      .eapps-widget:hover .eapps-widget-toolbar-panel {
        opacity: 1;
      }

      .eapps-widget-toolbar-panel-wrapper {
        width: 100%;
        position: relative;
      }

      .eapps-widget-toolbar-panel-only-you {
        position: absolute;
        top: -24px;
        font-size: 11px;
        line-height: 14px;
        color: rgb(156, 156, 156);
        padding: 5px 4px;
      }

      .eapps-widget-toolbar-panel-logo {
        width: 28px;
        height: 28px;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .eapps-widget-toolbar-panel-logo svg {
        display: block;
        width: 15px;
        height: 15px;
        fill: rgb(249, 50, 98);
      }

      .eapps-widget-toolbar-panel-edit {
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
        display: inline-flex;
        align-items: center;
        padding: 9px;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        color: rgb(255, 255, 255);
        text-decoration: none;
      }

      .eapps-widget-toolbar-panel-edit-icon {
        width: 14px;
        height: 14px;
        margin-right: 8px;
      }

      .eapps-widget-toolbar-panel-edit-icon svg {
        display: block;
        width: 100%;
        height: 100%;
        fill: rgb(255, 255, 255);
      }

      .eapps-widget-toolbar-panel-views {
        display: inline-flex;
        justify-content: center;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-views-label {
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
        margin-left: 8px;
      }

      .eapps-widget-toolbar-panel-views-bar {
        display: inline-flex;
        width: 70px;
        height: 3px;
        border-radius: 2px;
        margin-left: 8px;
        background-color: rgba(255, 255, 255, 0.3);
      }

      .eapps-widget-toolbar-panel-views-bar-inner {
        border-radius: 2px;
        background-color: rgb(74, 213, 4);
      }

      .eapps-widget-toolbar-panel-views-green .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(74, 213, 4);
      }

      .eapps-widget-toolbar-panel-views-red .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(255, 71, 52);
      }

      .eapps-widget-toolbar-panel-views-orange .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(255, 180, 0);
      }

      .eapps-widget-toolbar-panel-views-percent {
        display: inline-flex;
        margin-left: 8px;
        margin-right: 8px;
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
      }

      .eapps-widget-toolbar-panel-views-get-more {
        padding: 9px 16px;
        background-color: rgb(249, 50, 98);
        color: rgb(255, 255, 255);
        font-size: 12px;
        font-weight: 400;
        border-radius: 0px 6px 6px 0px;
      }

      .eapps-widget-toolbar-panel-share {
        position: absolute;
        top: 0px;
        display: inline-block;
        margin-left: 8px;
        width: 83px;
        height: 28px;
        padding-bottom: 4px;
        box-sizing: content-box !important;
      }

      .eapps-widget-toolbar-panel-share:hover .eapps-widget-toolbar-panel-share-block {
        opacity: 1;
        pointer-events: all;
      }

      .eapps-widget-toolbar-panel-share-button {
        padding: 0px 18px;
        height: 28px;
        background-color: rgb(28, 145, 255);
        color: rgb(255, 255, 255);
        font-size: 12px;
        font-weight: 400;
        border-radius: 6px;
        position: absolute;
        top: 0px;
        display: flex;
        flex-direction: row;
        cursor: default;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-share-button svg {
        display: inline-block;
        margin-right: 6px;
        fill: rgb(255, 255, 255);
        position: relative;
        top: -1px;
      }

      .eapps-widget-toolbar-panel-share-block {
        position: absolute;
        background: rgb(255, 255, 255);
        border: 1px solid rgba(18, 18, 18, 0.1);
        border-radius: 10px;
        width: 209px;
        top: 32px;
        transform: translateX(-63px);
        opacity: 0;
        pointer-events: none;
        transition: 0.3s;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 4px 6px;
      }

      .eapps-widget-toolbar-panel-share-block:hover {
        opacity: 1;
        pointer-events: all;
      }

      .eapps-widget-toolbar-panel-share-block-text {
        color: rgb(17, 17, 17);
        font-size: 15px;
        font-weight: 400;
        padding: 12px 0px;
        text-align: center;
      }

      .eapps-widget-toolbar-panel-share-block-text-icon {
        padding-bottom: 4px;
      }

      .eapps-widget-toolbar-panel-share-block-actions {
        display: flex;
        flex-direction: row;
        border-top: 1px solid rgba(18, 18, 18, 0.1);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item {
        width: 33.3333%;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 39px;
        transition: 0.3s;
        background-color: transparent;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item:hover {
        background-color: rgb(250, 250, 250);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item a {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        width: 16px;
        height: 16px;
        display: block;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-facebook
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(60, 90, 155);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-twitter
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(26, 178, 232);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-google
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(221, 75, 57);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item:not(:last-child) {
        border-right: 1px solid rgba(18, 18, 18, 0.1);
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      div.eapps-widget {
        position: relative;
      }

      div.eapps-widget.eapps-widget-show-toolbar::before {
        position: absolute;
        content: "";
        display: block;
        inset: 0px;
        pointer-events: none;
        border: 1px solid transparent;
        transition: border 0.3s;
        z-index: 1;
      }

      .eapps-widget-toolbar {
        position: absolute;
        top: -32px;
        left: 0px;
        right: 0px;
        display: block;
        z-index: 99999;
        padding-bottom: 4px;
        transition: 0.3s;
        pointer-events: none;
        opacity: 0;
      }

      .eapps-widget:hover .eapps-widget-toolbar {
        opacity: 1;
        pointer-events: auto;
      }

      .eapps-widget-toolbar a {
        text-decoration: none;
        box-shadow: none !important;
      }

      .eapps-widget-toolbar-panel {
        border-radius: 6px;
        background-color: rgb(34, 34, 34);
        color: rgb(255, 255, 255);
        display: inline-flex;
        align-items: center;
        top: 0px;
        position: relative;
        transition: 0.3s;
        opacity: 0;
        overflow: hidden;
        backface-visibility: hidden;
        box-shadow: rgba(255, 255, 255, 0.2) 0px 0px 0px 1px;
        height: 28px;
      }

      .eapps-widget:hover .eapps-widget-toolbar-panel {
        opacity: 1;
      }

      .eapps-widget-toolbar-panel-wrapper {
        width: 100%;
        position: relative;
      }

      .eapps-widget-toolbar-panel-only-you {
        position: absolute;
        top: -24px;
        font-size: 11px;
        line-height: 14px;
        color: rgb(156, 156, 156);
        padding: 5px 4px;
      }

      .eapps-widget-toolbar-panel-logo {
        width: 28px;
        height: 28px;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .eapps-widget-toolbar-panel-logo svg {
        display: block;
        width: 15px;
        height: 15px;
        fill: rgb(249, 50, 98);
      }

      .eapps-widget-toolbar-panel-edit {
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
        display: inline-flex;
        align-items: center;
        padding: 9px;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        color: rgb(255, 255, 255);
        text-decoration: none;
      }

      .eapps-widget-toolbar-panel-edit-icon {
        width: 14px;
        height: 14px;
        margin-right: 8px;
      }

      .eapps-widget-toolbar-panel-edit-icon svg {
        display: block;
        width: 100%;
        height: 100%;
        fill: rgb(255, 255, 255);
      }

      .eapps-widget-toolbar-panel-views {
        display: inline-flex;
        justify-content: center;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-views-label {
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
        margin-left: 8px;
      }

      .eapps-widget-toolbar-panel-views-bar {
        display: inline-flex;
        width: 70px;
        height: 3px;
        border-radius: 2px;
        margin-left: 8px;
        background-color: rgba(255, 255, 255, 0.3);
      }

      .eapps-widget-toolbar-panel-views-bar-inner {
        border-radius: 2px;
        background-color: rgb(74, 213, 4);
      }

      .eapps-widget-toolbar-panel-views-green .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(74, 213, 4);
      }

      .eapps-widget-toolbar-panel-views-red .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(255, 71, 52);
      }

      .eapps-widget-toolbar-panel-views-orange .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(255, 180, 0);
      }

      .eapps-widget-toolbar-panel-views-percent {
        display: inline-flex;
        margin-left: 8px;
        margin-right: 8px;
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
      }

      .eapps-widget-toolbar-panel-views-get-more {
        padding: 9px 16px;
        background-color: rgb(249, 50, 98);
        color: rgb(255, 255, 255);
        font-size: 12px;
        font-weight: 400;
        border-radius: 0px 6px 6px 0px;
      }

      .eapps-widget-toolbar-panel-share {
        position: absolute;
        top: 0px;
        display: inline-block;
        margin-left: 8px;
        width: 83px;
        height: 28px;
        padding-bottom: 4px;
        box-sizing: content-box !important;
      }

      .eapps-widget-toolbar-panel-share:hover .eapps-widget-toolbar-panel-share-block {
        opacity: 1;
        pointer-events: all;
      }

      .eapps-widget-toolbar-panel-share-button {
        padding: 0px 18px;
        height: 28px;
        background-color: rgb(28, 145, 255);
        color: rgb(255, 255, 255);
        font-size: 12px;
        font-weight: 400;
        border-radius: 6px;
        position: absolute;
        top: 0px;
        display: flex;
        flex-direction: row;
        cursor: default;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-share-button svg {
        display: inline-block;
        margin-right: 6px;
        fill: rgb(255, 255, 255);
        position: relative;
        top: -1px;
      }

      .eapps-widget-toolbar-panel-share-block {
        position: absolute;
        background: rgb(255, 255, 255);
        border: 1px solid rgba(18, 18, 18, 0.1);
        border-radius: 10px;
        width: 209px;
        top: 32px;
        transform: translateX(-63px);
        opacity: 0;
        pointer-events: none;
        transition: 0.3s;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 4px 6px;
      }

      .eapps-widget-toolbar-panel-share-block:hover {
        opacity: 1;
        pointer-events: all;
      }

      .eapps-widget-toolbar-panel-share-block-text {
        color: rgb(17, 17, 17);
        font-size: 15px;
        font-weight: 400;
        padding: 12px 0px;
        text-align: center;
      }

      .eapps-widget-toolbar-panel-share-block-text-icon {
        padding-bottom: 4px;
      }

      .eapps-widget-toolbar-panel-share-block-actions {
        display: flex;
        flex-direction: row;
        border-top: 1px solid rgba(18, 18, 18, 0.1);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item {
        width: 33.3333%;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 39px;
        transition: 0.3s;
        background-color: transparent;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item:hover {
        background-color: rgb(250, 250, 250);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item a {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        width: 16px;
        height: 16px;
        display: block;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-facebook
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(60, 90, 155);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-twitter
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(26, 178, 232);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-google
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(221, 75, 57);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item:not(:last-child) {
        border-right: 1px solid rgba(18, 18, 18, 0.1);
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      div.eapps-widget {
        position: relative;
      }

      div.eapps-widget.eapps-widget-show-toolbar::before {
        position: absolute;
        content: "";
        display: block;
        inset: 0px;
        pointer-events: none;
        border: 1px solid transparent;
        transition: border 0.3s;
        z-index: 1;
      }

      .eapps-widget-toolbar {
        position: absolute;
        top: -32px;
        left: 0px;
        right: 0px;
        display: block;
        z-index: 99999;
        padding-bottom: 4px;
        transition: 0.3s;
        pointer-events: none;
        opacity: 0;
      }

      .eapps-widget:hover .eapps-widget-toolbar {
        opacity: 1;
        pointer-events: auto;
      }

      .eapps-widget-toolbar a {
        text-decoration: none;
        box-shadow: none !important;
      }

      .eapps-widget-toolbar-panel {
        border-radius: 6px;
        background-color: rgb(34, 34, 34);
        color: rgb(255, 255, 255);
        display: inline-flex;
        align-items: center;
        top: 0px;
        position: relative;
        transition: 0.3s;
        opacity: 0;
        overflow: hidden;
        backface-visibility: hidden;
        box-shadow: rgba(255, 255, 255, 0.2) 0px 0px 0px 1px;
        height: 28px;
      }

      .eapps-widget:hover .eapps-widget-toolbar-panel {
        opacity: 1;
      }

      .eapps-widget-toolbar-panel-wrapper {
        width: 100%;
        position: relative;
      }

      .eapps-widget-toolbar-panel-only-you {
        position: absolute;
        top: -24px;
        font-size: 11px;
        line-height: 14px;
        color: rgb(156, 156, 156);
        padding: 5px 4px;
      }

      .eapps-widget-toolbar-panel-logo {
        width: 28px;
        height: 28px;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .eapps-widget-toolbar-panel-logo svg {
        display: block;
        width: 15px;
        height: 15px;
        fill: rgb(249, 50, 98);
      }

      .eapps-widget-toolbar-panel-edit {
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
        display: inline-flex;
        align-items: center;
        padding: 9px;
        border-right: 1px solid rgba(255, 255, 255, 0.2);
        color: rgb(255, 255, 255);
        text-decoration: none;
      }

      .eapps-widget-toolbar-panel-edit-icon {
        width: 14px;
        height: 14px;
        margin-right: 8px;
      }

      .eapps-widget-toolbar-panel-edit-icon svg {
        display: block;
        width: 100%;
        height: 100%;
        fill: rgb(255, 255, 255);
      }

      .eapps-widget-toolbar-panel-views {
        display: inline-flex;
        justify-content: center;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-views-label {
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
        margin-left: 8px;
      }

      .eapps-widget-toolbar-panel-views-bar {
        display: inline-flex;
        width: 70px;
        height: 3px;
        border-radius: 2px;
        margin-left: 8px;
        background-color: rgba(255, 255, 255, 0.3);
      }

      .eapps-widget-toolbar-panel-views-bar-inner {
        border-radius: 2px;
        background-color: rgb(74, 213, 4);
      }

      .eapps-widget-toolbar-panel-views-green .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(74, 213, 4);
      }

      .eapps-widget-toolbar-panel-views-red .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(255, 71, 52);
      }

      .eapps-widget-toolbar-panel-views-orange .eapps-widget-toolbar-panel-views-bar-inner {
        background-color: rgb(255, 180, 0);
      }

      .eapps-widget-toolbar-panel-views-percent {
        display: inline-flex;
        margin-left: 8px;
        margin-right: 8px;
        font-size: 12px;
        font-weight: 400;
        line-height: 14px;
      }

      .eapps-widget-toolbar-panel-views-get-more {
        padding: 9px 16px;
        background-color: rgb(249, 50, 98);
        color: rgb(255, 255, 255);
        font-size: 12px;
        font-weight: 400;
        border-radius: 0px 6px 6px 0px;
      }

      .eapps-widget-toolbar-panel-share {
        position: absolute;
        top: 0px;
        display: inline-block;
        margin-left: 8px;
        width: 83px;
        height: 28px;
        padding-bottom: 4px;
        box-sizing: content-box !important;
      }

      .eapps-widget-toolbar-panel-share:hover .eapps-widget-toolbar-panel-share-block {
        opacity: 1;
        pointer-events: all;
      }

      .eapps-widget-toolbar-panel-share-button {
        padding: 0px 18px;
        height: 28px;
        background-color: rgb(28, 145, 255);
        color: rgb(255, 255, 255);
        font-size: 12px;
        font-weight: 400;
        border-radius: 6px;
        position: absolute;
        top: 0px;
        display: flex;
        flex-direction: row;
        cursor: default;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-share-button svg {
        display: inline-block;
        margin-right: 6px;
        fill: rgb(255, 255, 255);
        position: relative;
        top: -1px;
      }

      .eapps-widget-toolbar-panel-share-block {
        position: absolute;
        background: rgb(255, 255, 255);
        border: 1px solid rgba(18, 18, 18, 0.1);
        border-radius: 10px;
        width: 209px;
        top: 32px;
        transform: translateX(-63px);
        opacity: 0;
        pointer-events: none;
        transition: 0.3s;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 4px 6px;
      }

      .eapps-widget-toolbar-panel-share-block:hover {
        opacity: 1;
        pointer-events: all;
      }

      .eapps-widget-toolbar-panel-share-block-text {
        color: rgb(17, 17, 17);
        font-size: 15px;
        font-weight: 400;
        padding: 12px 0px;
        text-align: center;
      }

      .eapps-widget-toolbar-panel-share-block-text-icon {
        padding-bottom: 4px;
      }

      .eapps-widget-toolbar-panel-share-block-actions {
        display: flex;
        flex-direction: row;
        border-top: 1px solid rgba(18, 18, 18, 0.1);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item {
        width: 33.3333%;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 39px;
        transition: 0.3s;
        background-color: transparent;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item:hover {
        background-color: rgb(250, 250, 250);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item a {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        width: 16px;
        height: 16px;
        display: block;
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-facebook
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(60, 90, 155);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-twitter
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(26, 178, 232);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item-google
        .eapps-widget-toolbar-panel-share-block-actions-item-icon {
        fill: rgb(221, 75, 57);
      }

      .eapps-widget-toolbar-panel-share-block-actions-item:not(:last-child) {
        border-right: 1px solid rgba(18, 18, 18, 0.1);
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      .mobile-menu-overlay {
        position: fixed;
        inset: 0px;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 900;
        display: none;
        pointer-events: none;
      }

      .mobile-menu-overlay.is-active {
        display: block;
        pointer-events: auto;
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      .w-nav-overlay {
        z-index: 1198 !important;
      }

      .w-nav-overlay .w-nav-menu {
        position: fixed;
        left: 0px;
        right: 0px;
        z-index: 1199 !important;
      }

      #menuDimmer {
        position: fixed;
        left: 0px;
        right: 0px;
        bottom: 0px;
        background: rgba(0, 0, 0, 0.55);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s;
        z-index: 1197;
      }

    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      body:not(.mobile-menu-open) .w-nav-overlay {
        display: none !important;
        pointer-events: none !important;
        height: auto !important;
      }

      body.mobile-menu-open .w-nav-overlay {
        pointer-events: auto !important;
      }

      body.mobile-menu-open .w-nav-overlay .w-nav-menu {
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      .video-bg-container {
        position: absolute;
        top: 0px;
        left: 0px;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
        background-size: cover;
        background-position: center center;
      }

      .video-bg-container video {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

@media screen and (min-width: 480px) {
        .third-item .w-dyn-item:nth-child(4) {
          margin-left: -24px;
        }
      }
    </style>
    <style rel="stylesheet" type="text/css">
      @charset "utf-8";

      .scroll-block {
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgb(231, 152, 0) transparent;
      }

      .scroll-block::-webkit-scrollbar {
        width: 6px;
      }

      .scroll-block::-webkit-scrollbar-thumb {
        background: rgb(231, 152, 0);
        border-radius: 999px;
      }
    </style>

    <script type="text/javascript">
      window.__WEBFLOW_CURRENCY_SETTINGS = {
        currencyCode: "USD",
        symbol: "$",
        decimal: ".",
        fractionDigits: 2,
        group: ",",
        template:
          '@{{wf {"path":"symbol","type":"PlainText"} }} @{{wf {"path":"amount","type":"CommercePrice"} }} @{{wf {"path":"currencyCode","type":"PlainText"} }}',
        hideDecimalForWholeNumbers: false,
      };
    </script>
    {{-- Inlined to avoid an extra render-blocking request; source lives in public/webflow-overrides/mobile-home.css --}}
    @php $mobileHomeCssPath = public_path('webflow-overrides/mobile-home.css'); @endphp
    @if (is_file($mobileHomeCssPath))
      <style>{!! file_get_contents($mobileHomeCssPath) !!}</style>
    @endif
@endsection

@section('content')
    @include('partials.hero')

    @include('partials.trust-badges')

    @include('partials.brands')

    @include('partials.windows', ['homeWindows' => $homeWindows])

    @include('partials.doors')

    @include('partials.reviews')

    @include('partials.guarantee')

    @include('partials.certifications')

    @include('partials.for-professionals')

    @include('partials.cta')

@endsection

@section('bodyScripts')
  <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript" defer></script>
  <script src="/webflow-assets/js/webflow.js" type="text/javascript" defer></script>
@endsection