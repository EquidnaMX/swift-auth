/**
 * SwiftAuth translations for JavaScript/TypeScript frontend.
 *
 * These translations are loaded from Laravel backend.
 * Usage: import { __ } from './translations';
 *        __('auth.login_title')
 */

// Get translations from backend (passed via Inertia or window object)
const translations =
    typeof window !== "undefined" && window.swiftAuthTranslations
        ? window.swiftAuthTranslations
        : {};

const locale =
    typeof window !== "undefined" && window.swiftAuthLocale
        ? window.swiftAuthLocale
        : "en";

/**
 * Translation function - mirrors Laravel's __() helper.
 *
 * @param {string} key - Translation key (e.g., 'auth.login_title')
 * @param {Object} replacements - Key-value pairs for placeholder replacement
 * @returns {string} Translated string
 */
export function __(key, replacements = {}) {
    let translation = translations[key] || key;

    // Replace placeholders like :name, :seconds
    Object.keys(replacements).forEach((placeholder) => {
        translation = translation.replace(
            new RegExp(`:${placeholder}`, "g"),
            replacements[placeholder]
        );
    });

    return translation;
}

/**
 * Get current locale.
 *
 * @returns {string} Current locale code (e.g., 'en', 'es')
 */
export function locale() {
    return getCurrentLocale();
}

/**
 * Gets fallback translations based on current locale.
 *
 * @returns {Object} Fallback translation map
 */
function getFallbackTranslations() {
    const loc = getCurrentLocale();
    return loc === "es" ? es : en;
}

// Default English translations (fallback)
export const en = {
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
export const es = {
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
