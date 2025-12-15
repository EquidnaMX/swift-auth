import React from "react";

const RequestSent: React.FC = () => (
    <div className="password-request-sent">
        <h2>Reset Link Sent</h2>
        <p>
            If your email is registered, you will receive a password reset link
            shortly.
        </p>
        <a href="/login">Return to Login</a>
    </div>
);

export default RequestSent;
