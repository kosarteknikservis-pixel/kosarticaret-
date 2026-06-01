@php
    $params = $params ?? $this->params();
    $renderCampaignField = function (?string $value) use ($params): string {
        $value = (string) $value;
        foreach ($params as $key => $param) {
            $value = str_replace('{{'.$key.'}}', $param, $value);
        }

        return \Illuminate\Support\Str::of($value)->replaceMatches('/\{\{[a-zA-Z0-9_]+\}\}/', '')->toString();
    };
    $subject = $renderCampaignField($campaign->subject);
    $preheader = $renderCampaignField($campaign->preheader);
    $title = $renderCampaignField($campaign->title);
    $body = \App\Support\SafeMailHtml::render($renderCampaignField($campaign->body), $campaign->body_is_html);
    $buttonLabel = $renderCampaignField($campaign->button_label);
    $buttonUrl = $renderCampaignField($campaign->button_url);
    $footerNote = 'Bu e-postayı KOŞAR Ticaret bültenine abone olduğunuz için aldınız.';
    $imageUrl = $campaign->image_url;
@endphp

@extends('emails.layouts.kosar')
