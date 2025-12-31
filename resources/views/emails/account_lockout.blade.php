<div style="max-width: 500px; margin: 0 auto; padding: 24px; font-family: Arial, sans-serif; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center;">
    <div style="margin-bottom: 20px;">
        <div style="font-size: 48px; margin-bottom: 12px;">🔒</div>
    </div>

    <h2 style="color: #1f2937; font-size: 22px; margin-bottom: 8px;">@lang('swift-auth::email.lockout_title')</h2>

    <p style="color: #4b5563; font-size: 15px; margin-bottom: 16px; line-height: 1.6;">
        @lang('swift-auth::email.lockout_message')
    </p>

    <div style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; text-align: left;">
        <strong>@lang('swift-auth::email.lockout_duration', ['minutes' => $minutes])</strong>
    </div>

    <p style="color: #6b7280; font-size: 13px; margin-top: 20px; line-height: 1.5;">
        @lang('swift-auth::email.lockout_contact')
    </p>
</div>
