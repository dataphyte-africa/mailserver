@php
    $socialLinks = $footerConfig['social_links'] ?? [];
    $offices = $footerConfig['offices'] ?? [];
@endphp

<tr>
    <td style="background-color:#f6f2eb;padding:30px 32px;border-top:1px solid #e7ddd1;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td style="padding:0 0 18px;text-align:center;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                        <tr>
                            @foreach ([
                                'facebook' => ['label' => 'Facebook', 'path' => 'M13.5 8.25H11.25V6.75C11.25 6.336 11.586 6 12 6H13.5V3H11.25C9.179 3 7.5 4.679 7.5 6.75V8.25H5.25V11.25H7.5V21H11.25V11.25H13.125L13.5 8.25Z'],
                                'twitter' => ['label' => 'Twitter', 'path' => 'M18.244 6.276C18.256 6.45 18.256 6.624 18.256 6.8C18.256 12.19 14.154 18.404 6.652 18.404C4.346 18.404 2.2 17.74 0.375 16.61C0.702 16.648 1.016 16.66 1.356 16.66C3.258 16.66 5.014 16.02 6.418 14.916C4.638 14.88 3.142 13.704 2.62 12.046C2.87 12.084 3.118 12.108 3.38 12.108C3.744 12.108 4.11 12.058 4.45 11.97C2.596 11.594 1.204 9.958 1.204 7.998V7.948C1.74 8.246 2.362 8.434 3.018 8.46C1.93 7.744 1.216 6.508 1.216 5.108C1.216 4.38 1.414 3.714 1.75 3.124C3.706 5.522 6.636 7.086 9.942 7.256C9.88 6.97 9.844 6.674 9.844 6.376C9.844 4.186 11.62 2.41 13.81 2.41C14.954 2.41 15.988 2.89 16.716 3.666C17.592 3.492 18.43 3.174 19.19 2.73C18.904 3.63 18.304 4.392 17.524 4.878C18.296 4.792 19.032 4.584 19.71 4.296C19.184 5.056 18.516 5.732 17.742 6.248L18.244 6.276Z'],
                                'whatsapp' => ['label' => 'WhatsApp Channel', 'path' => 'M10.507 2.25C6.024 2.25 2.383 5.853 2.383 10.297C2.383 11.717 2.754 13.104 3.458 14.332L2.25 18.75L6.808 17.563C7.983 18.197 9.304 18.531 10.652 18.531H10.655C15.136 18.531 18.78 14.926 18.78 10.483C18.78 6.04 15.136 2.25 10.507 2.25ZM14.89 13.807C14.703 14.333 13.78 14.801 13.324 14.87C12.868 14.94 12.302 14.97 10.592 14.304C8.454 13.47 7.06 10.992 6.95 10.846C6.84 10.702 6.087 9.7 6.087 8.662C6.087 7.624 6.636 7.114 6.829 6.91C7.021 6.704 7.25 6.655 7.385 6.655C7.52 6.655 7.656 6.657 7.772 6.662C7.883 6.668 8.035 6.62 8.184 6.977C8.373 7.43 8.826 8.54 8.88 8.651C8.935 8.763 8.972 8.893 8.894 9.036C8.816 9.181 8.777 9.27 8.661 9.402C8.545 9.534 8.418 9.697 8.315 9.813C8.199 9.945 8.079 10.086 8.222 10.33C8.366 10.574 8.862 11.378 9.59 12.027C10.525 12.86 11.314 13.12 11.579 13.236C11.843 13.352 11.999 13.335 12.135 13.19C12.27 13.046 12.713 12.53 12.869 12.297C13.025 12.064 13.181 12.103 13.407 12.181C13.633 12.258 14.838 12.85 15.083 12.968C15.328 13.084 15.489 13.142 15.547 13.239C15.604 13.335 15.604 13.797 15.417 14.323L14.89 13.807Z'],
                                'youtube' => ['label' => 'YouTube', 'path' => 'M18.75 7.467C18.596 6.891 18.146 6.44 17.57 6.287C16.513 6 12.25 6 12.25 6C12.25 6 7.987 6 6.93 6.287C6.354 6.44 5.904 6.891 5.75 7.467C5.463 8.524 5.463 10.75 5.463 10.75C5.463 10.75 5.463 12.976 5.75 14.033C5.904 14.609 6.354 15.06 6.93 15.213C7.987 15.5 12.25 15.5 12.25 15.5C12.25 15.5 16.513 15.5 17.57 15.213C18.146 15.06 18.596 14.609 18.75 14.033C19.037 12.976 19.037 10.75 19.037 10.75C19.037 10.75 19.037 8.524 18.75 7.467ZM10.875 12.787V8.713L14.438 10.75L10.875 12.787Z'],
                                'instagram' => ['label' => 'Instagram', 'path' => 'M12 6.375C8.893 6.375 6.375 8.893 6.375 12C6.375 15.107 8.893 17.625 12 17.625C15.107 17.625 17.625 15.107 17.625 12C17.625 8.893 15.107 6.375 12 6.375ZM12 15.75C9.929 15.75 8.25 14.071 8.25 12C8.25 9.929 9.929 8.25 12 8.25C14.071 8.25 15.75 9.929 15.75 12C15.75 14.071 14.071 15.75 12 15.75ZM18 5.812C18 6.537 17.412 7.125 16.687 7.125C15.963 7.125 15.375 6.537 15.375 5.812C15.375 5.088 15.963 4.5 16.687 4.5C17.412 4.5 18 5.088 18 5.812ZM21.75 7.144C21.666 5.365 21.259 3.79 19.956 2.487C18.653 1.184 17.078 0.777 15.299 0.693C13.468 0.591 10.532 0.591 8.701 0.693C6.922 0.777 5.347 1.184 4.044 2.487C2.741 3.79 2.334 5.365 2.25 7.144C2.148 8.975 2.148 11.911 2.25 13.742C2.334 15.521 2.741 17.096 4.044 18.399C5.347 19.702 6.922 20.109 8.701 20.193C10.532 20.295 13.468 20.295 15.299 20.193C17.078 20.109 18.653 19.702 19.956 18.399C21.259 17.096 21.666 15.521 21.75 13.742C21.852 11.911 21.852 8.975 21.75 7.144ZM19.523 15.957C19.136 16.932 18.386 17.682 17.411 18.069C15.951 18.649 12.494 18.516 12 18.516C11.506 18.516 8.049 18.649 6.589 18.069C5.614 17.682 4.864 16.932 4.477 15.957C3.897 14.497 4.03 11.04 4.03 10.546C4.03 10.052 3.897 6.595 4.477 5.135C4.864 4.16 5.614 3.41 6.589 3.023C8.049 2.443 11.506 2.576 12 2.576C12.494 2.576 15.951 2.443 17.411 3.023C18.386 3.41 19.136 4.16 19.523 5.135C20.103 6.595 19.97 10.052 19.97 10.546C19.97 11.04 20.103 14.497 19.523 15.957Z'],
                                'tiktok' => ['label' => 'TikTok', 'path' => 'M14.308 2.25C14.477 3.682 15.309 4.986 16.57 5.69C17.363 6.146 18.286 6.385 19.23 6.379V9.197C18.007 9.216 16.809 8.846 15.812 8.142V14.047C15.812 17.23 13.23 19.812 10.047 19.812C6.864 19.812 4.282 17.23 4.282 14.047C4.282 10.864 6.864 8.282 10.047 8.282C10.279 8.282 10.507 8.295 10.731 8.323V11.204C10.517 11.144 10.285 11.111 10.047 11.111C8.425 11.111 7.111 12.425 7.111 14.047C7.111 15.669 8.425 16.983 10.047 16.983C11.669 16.983 12.983 15.669 12.983 14.047V2.25H14.308Z'],
                            ] as $platform => $icon)
                                <td style="padding:0 6px;">
                                    <a href="{{ $socialLinks[$platform] ?? '#' }}" aria-label="{{ $icon['label'] }}" style="display:block;width:34px;height:34px;border-radius:17px;background-color:#ffffff;border:1px solid #ddd3c6;text-decoration:none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" style="display:block;">
                                            <circle cx="12" cy="12" r="11.5" fill="#ffffff"/>
                                            <path d="{{ $icon['path'] }}" fill="#3d405b"/>
                                        </svg>
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="padding:0 0 14px;text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.7;color:#6b7280;">
                    You're receiving this because you subscribed to {{ $fromName ?? config('app.name') }}.
                </td>
            </tr>

            <tr>
                <td style="padding:0 0 22px;text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#6b7280;">
                    <a href="{{ $preferencesUrl ?? '#' }}" style="color:#3d405b;text-decoration:underline;">Manage preferences</a>
                    &nbsp;&middot;&nbsp;
                    <a href="{{ $unsubscribeUrl ?? '#' }}" style="color:#3d405b;text-decoration:underline;">Unsubscribe</a>
                </td>
            </tr>

            @if(!empty($offices))
                <tr>
                    <td style="padding:0;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                            <tr>
                                @foreach($offices as $office)
                                    <td class="stack-column office-column" width="{{ floor(100 / count($offices)) }}%" valign="top" style="padding:12px 10px;border-top:1px solid #e7ddd1;text-align:left;">
                                        <p style="margin:0 0 6px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.5;font-weight:700;color:#1f2937;">
                                            {{ $office['label'] ?? '' }}
                                        </p>
                                        <p style="margin:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;color:#6b7280;">
                                            {{ $office['address'] ?? '' }}
                                        </p>
                                    </td>
                                @endforeach
                            </tr>
                        </table>
                    </td>
                </tr>
            @endif

            <tr>
                <td style="padding:18px 0 0;text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;font-size:11px;line-height:1.6;color:#8a8f98;">
                    &copy; {{ date('Y') }} {{ $fromName ?? config('app.name') }}. All rights reserved.
                </td>
            </tr>
        </table>
    </td>
</tr>
