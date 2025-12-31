/**
 * SwiftAuth translations for TypeScript frontend.
 *
 * These translations are loaded from Laravel backend via Inertia.
 * Usage: import { __ } from './translations';
 *        __('auth.login_title')
 */

interface Translations {
    [key: string]: string;
}

interface Replacements {
    [key: string]: string | number;
}

interface InertiaPage {
    props: {
        translations?: Translations;
        locale?: string;
    };
}

// Get translations from Inertia page props (preferred) or window fallback
declare global {
    interface Window {
        swiftAuthTranslations?: Translations;
        swiftAuthLocale?: string;
    }
}

/**
 * Gets translations from Inertia page props or window object.
 *
 * @returns Translation key-value map
 */
function getTranslations(): Translations {
    // Try to get from Inertia page props first
    if (
        typeof window !== "undefined" &&
        (window as any).page?.props?.translations
    ) {
        return (window as any).page.props.translations;
    }

    // Fallback to window object
    if (typeof window !== "undefined" && window.swiftAuthTranslations) {
        return window.swiftAuthTranslations;
    }

    // Return fallback translations
    return getFallbackTranslations();
}

/**
 * Gets current locale from Inertia or window.
 *
 * @returns Current locale code
 */
function getCurrentLocale(): string {
    // Try Inertia page props first
    if (typeof window !== "undefined" && (window as any).page?.props?.locale) {
        return (window as any).page.props.locale;
    }

    // Fallback to window object
    if (typeof window !== "undefined" && window.swiftAuthLocale) {
        return window.swiftAuthLocale;
    }

    return "en";
}

const translations: Translations = getTranslations();
const currentLocale: string = getCurrentLocale();

/**
 * Translation function - mirrors Laravel's __() helper.
 *
 * @param key - Translation key (e.g., 'auth.login_title')
 * @param replacements - Key-value pairs for placeholder replacement
 * @returns Translated string
 */
export function __(key: string, replacements: Replacements = {}): string {
    let translation = translations[key] || key;

    // Replace placeholders like :name, :seconds
    Object.keys(replacements).forEach((placeholder) => {
        translation = translation.replace(
            new RegExp(`:${placeholder}`, "g"),
            String(replacements[placeholder])
        );
    });

    return translation;
}

/**
 * Get current locale.
 *
 * @returns Current locale code (e.g., 'en', 'es')
 */
export function locale(): string {
    return getCurrentLocale();
}

/**
 * Gets fallback translations based on current locale.
 *
 * @returns Fallback translation map
 */
function getFallbackTranslations(): Translations {
    const locale = getCurrentLocale();
    return locale === "es" ? es : en;
}

// Default English translations (fallback)
export const en: Translations = {
    "auth.login_title": "Log In",
    "auth.login_button": "Log In",
    "auth.logout_button": "Log Out",
    "auth.loading": "Loading...",
    "auth.email": "Email",
    "auth.password": "Password",
    "auth.forgot_password": "Forgot your password?",
    "auth.register_title": "Register",
    "auth.register_button": "Register",
    "auth.registering": "Registering...",
    "auth.name": "Name",
    "auth.password_confirmation": "Confirm Password",
    "auth.submit": "Submit",
    "auth.cancel": "Cancel",
    "auth.saving": "Saving...",
    "auth.no_account": "Don't have an account? Register",
    "auth.already_have_account": "Already have an account? Log in",
    "nav.home": "Home",
    "nav.users": "Users",
    "nav.roles": "Roles",
    "user.users": "Users",
    "user.name": "Name",
    "user.email": "Email",
    "user.actions": "Actions",
    "user.create": "Create User",
    "user.new_user": "New User",
    "user.edit": "Edit User",
    "user.save": "Save",
    "user.delete": "Delete",
    "role.roles": "Roles",
    "role.name": "Name",
    "role.new_role": "New Role",
    "role.add_role": "Add Role",
    "role.edit_role": "Edit Role",
    "role.actions": "Actions",
    "role.no_roles": "No roles registered.",
};

// Spanish translations
export const es: Translations = {
    "auth.login_title": "Iniciar sesión",
    "auth.login_button": "Iniciar sesión",
    "auth.logout_button": "Cerrar sesión",
    "auth.loading": "Cargando...",
    "auth.email": "Correo electrónico",
    "auth.password": "Contraseña",
    "auth.forgot_password": "¿Olvidaste tu contraseña?",
    "auth.register_title": "Registrarse",
    "auth.register_button": "Registrarse",
    "auth.registering": "Registrando...",
    "auth.name": "Nombre",
    "auth.password_confirmation": "Confirmar contraseña",
    "auth.submit": "Enviar",
    "auth.cancel": "Cancelar",
    "auth.saving": "Enviando...",
    "auth.no_account": "¿No tienes cuenta? Regístrate",
    "auth.already_have_account": "¿Ya tienes cuenta? Inicia sesión",
    "nav.home": "Inicio",
    "nav.users": "Usuarios",
    "nav.roles": "Roles",
    "user.users": "Usuarios",
    "user.name": "Nombre",
    "user.email": "Correo electrónico",
    "user.actions": "Acciones",
    "user.create": "Crear Usuario",
    "user.new_user": "Nuevo usuario",
    "user.edit": "Editar Usuario",
    "user.save": "Guardar",
    "user.delete": "Eliminar",
    "role.roles": "Roles",
    "role.ndd_role": "Agregar rol",
    "role.edit_role": "Editar rol",
    "role.aame": "Nombre",
    "role.new_role": "Nuevo rol",
    "role.actions": "Acciones",
    "role.no_roles": "No hay roles registrados.",
};
