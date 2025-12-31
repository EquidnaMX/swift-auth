import { router } from "@inertiajs/react";
import { useState } from "react";

interface LanguageSwitcherProps {
    currentLocale?: string;
    className?: string;
}

/**
 * Language switcher component for toggling between English and Spanish.
 *
 * @param currentLocale - Current application locale (default: 'en')
 * @param className - Optional CSS classes for styling
 */
export function LanguageSwitcher({
    currentLocale = "en",
    className = "",
}: LanguageSwitcherProps) {
    const [locale, setLocale] = useState(currentLocale);

    const handleSwitch = (newLocale: string) => {
        setLocale(newLocale);
        router.post(
            `/locale/${newLocale}`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    // Reload to apply new translations
                    window.location.reload();
                },
            }
        );
    };

    return (
        <div className={`flex items-center gap-2 ${className}`}>
            <button
                onClick={() => handleSwitch("en")}
                className={`px-3 py-1 rounded text-sm font-medium transition-colors ${
                    locale === "en"
                        ? "bg-blue-500 text-white"
                        : "bg-gray-200 text-gray-700 hover:bg-gray-300"
                }`}
                aria-label="Switch to English"
            >
                EN
            </button>
            <button
                onClick={() => handleSwitch("es")}
                className={`px-3 py-1 rounded text-sm font-medium transition-colors ${
                    locale === "es"
                        ? "bg-blue-500 text-white"
                        : "bg-gray-200 text-gray-700 hover:bg-gray-300"
                }`}
                aria-label="Cambiar a Español"
            >
                ES
            </button>
        </div>
    );
}
