@php
    $subject = $template->render('subject', $params);
    $preheader = $template->render('preheader', $params);
    $title = $template->render('title', $params);
    $body = \App\Support\SafeMailHtml::render($template->render('body', $params), $template->body_is_html);
    $buttonLabel = $template->render('button_label', $params);
    $buttonUrl = $template->render('button_url', $params);
    $footerNote = $template->render('footer_note', $params);
@endphp

@extends('emails.layouts.kosar')
