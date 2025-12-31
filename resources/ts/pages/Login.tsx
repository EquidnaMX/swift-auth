import { Link, useForm, Head } from "@inertiajs/react";
import { FormEvent, ReactNode } from "react";
import Guest from "../layouts/Guest";
import { __ } from "../../lang/translations";

const LoginForm = () => {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        password: "",
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(route("swift-auth.login"));
    };

    return (
        <>
            <Head title="Login" />

            <div className="max-w-md mx-auto mt-10 bg-white px-6 pt-6  rounded-lg shadow-md">
                <h2 className="text-2xl font-bold text-center mb-4">
                    {__("auth.login_title")}
                </h2>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium">
                            {__("auth.email")}
                        </label>
                        <input
                            type="email"
                            name="email"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            className="w-full border rounded px-3 py-2 mt-1"
                            autoFocus
                            required
                        />
                        {errors.email && (
                            <p className="text-red-500 text-sm">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium">
                            {__("auth.password")}
                        </label>
                        <input
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                            className="w-full border rounded px-3 py-2 mt-1"
                            required
                        />
                        {errors.password && (
                            <p className="text-red-500 text-sm">
                                {errors.password}
                            </p>
                        )}
                    </div>

                    <div className="">
                        <button
                            type="submit"
                            className="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 w-full rounded-lg"
                            disabled={processing}
                        >
                            {processing
                                ? __("auth.loading")
                                : __("auth.login_button")}
                        </button>
                        <div className="text-center">
                            <Link
                                href={route("swift-auth.password.request.form")}
                                className="text-sm text-blue-500"
                            >
                                {__("auth.forgot_password")}
                            </Link>
                        </div>
                    </div>
                </form>
                <div className="text-center p-4">
                    <Link
                        href={route("swift-auth.public.register")}
                        className="text-sm text-blue-500"
                    >
                        {__("auth.no_account")}
                    </Link>
                </div>
            </div>
        </>
    );
};

LoginForm.layout = (page: ReactNode) => <Guest>{page}</Guest>;

export default LoginForm;
