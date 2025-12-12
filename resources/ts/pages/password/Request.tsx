import React, { useState } from "react";

const Request: React.FC = () => {
    const [email, setEmail] = useState("");
    const [submitted, setSubmitted] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        // Simulate API call
        if (!email.match(/^\S+@\S+\.\S+$/)) {
            setError("Please enter a valid email address.");
            return;
        }
        setSubmitted(true);
    };

    if (submitted) {
        return (
            <div className="password-request-sent">
                <h2>Password Reset Requested</h2>
                <p>
                    If your email is registered, you will receive a password
                    reset link shortly.
                </p>
            </div>
        );
    }

    return (
        <div className="password-request">
            <h2>Request Password Reset</h2>
            <form onSubmit={handleSubmit}>
                <label htmlFor="email">Email Address</label>
                <input
                    id="email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                />
                {error && <p className="error">{error}</p>}
                <button type="submit">Send Reset Link</button>
            </form>
        </div>
    );
};

export default Request;
