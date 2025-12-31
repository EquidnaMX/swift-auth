<div style="max-width: 500px; margin: 0 auto; padding: 24px; font-family: Arial, sans-serif; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center;">
    <div style="margin-bottom: 20px;">
        <div style="font-size: 48px; margin-bottom: 12px;">✅</div>
    </div>

    <h2 style="color: #1f2937; font-size: 22px; margin-bottom: 8px;">@lang('swift-auth::email.verification_title')</h2>

    <p style="color: #4b5563; font-size: 15px; margin-bottom: 16px; line-height: 1.6;">
        @lang('swift-auth::email.verification_message')
    </p>

    <a href="{{ $verifyUrl }}" style="display: inline-block; background-color: #10b981; color: #ffffff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 15px; margin-bottom: 20px;">
        @lang('swift-auth::email.verification_button')
    </a>

    <p style="color: #6b7280; font-size: 13px; margin-top: 20px; line-height: 1.5;">
        @lang('swift-auth::email.verification_footer')
    </p>
</div>
