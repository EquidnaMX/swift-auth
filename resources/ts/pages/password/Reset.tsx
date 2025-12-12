import React, { useState } from "react";

const Reset: React.FC = () => {
    const [password, setPassword] = useState("");
    const [confirm, setConfirm] = useState("");
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        if (password.length < 8) {
            setError("Password must be at least 8 characters.");
            return;
        }
        if (password !== confirm) {
            setError("Passwords do not match.");
            return;
        }
        // Simulate API call
        setSuccess(true);
    };

    if (success) {
        return (
            <div className="password-reset-success">
                <h2>Password Reset Successful</h2>
                <p>
                    Your password has been updated. You may now{" "}
                    <a href="/login">log in</a>.
                </p>
            </div>
        );
    }

    return (
        <div className="password-reset">
            <h2>Set New Password</h2>
            <form onSubmit={handleSubmit}>
                <label htmlFor="password">New Password</label>
                <input
                    id="password"
                    type="password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                />
                <label htmlFor="confirm">Confirm Password</label>
                <input
                    id="confirm"
                    type="password"
                    value={confirm}
                    onChange={(e) => setConfirm(e.target.value)}
                    required
                />
                {error && <p className="error">{error}</p>}
                <button type="submit">Reset Password</button>
            </form>
        </div>
    );
};

export default Reset;
