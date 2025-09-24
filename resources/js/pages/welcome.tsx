import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="CSDT - Consejo Social de Veeduría y Desarrollo Territorial">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-gradient-to-br from-blue-50 to-indigo-100 p-6 text-gray-900 lg:justify-center lg:p-8 dark:from-gray-900 dark:to-gray-800 dark:text-gray-100">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-block rounded-lg border border-blue-300 bg-blue-600 px-5 py-1.5 text-sm leading-normal text-white hover:bg-blue-700 dark:border-blue-600 dark:bg-blue-700 dark:hover:bg-blue-800"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-block rounded-lg border border-transparent px-5 py-1.5 text-sm leading-normal text-blue-600 hover:border-blue-300 hover:bg-blue-50 dark:text-blue-400 dark:hover:border-blue-600 dark:hover:bg-blue-900/20"
                                >
                                    Iniciar Sesión
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-block rounded-lg border border-blue-300 bg-blue-600 px-5 py-1.5 text-sm leading-normal text-white hover:bg-blue-700 dark:border-blue-600 dark:bg-blue-700 dark:hover:bg-blue-800"
                                >
                                    Registrarse
                                </Link>
                            </>
                        )}
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-[335px] flex-col-reverse lg:max-w-4xl lg:flex-row">
                        <div className="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-lg lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-gray-800 dark:text-gray-100">
                            <div className="mb-6 flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white">
                                    <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h1 className="text-xl font-bold text-blue-600 dark:text-blue-400">CSDT</h1>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Consejo Social de Veeduría</p>
                                </div>
                            </div>
                            
                            <h2 className="mb-4 text-2xl font-bold text-gray-900 dark:text-white">
                                Bienvenido al Sistema de Veeduría Ciudadana
                            </h2>
                            
                            <p className="mb-6 text-gray-700 dark:text-gray-300">
                                El Consejo Social de Veeduría y Desarrollo Territorial (CSDT) es una plataforma 
                                integral para la participación ciudadana, el control social y el desarrollo territorial 
                                sostenible.
                            </p>

                            <div className="mb-6 grid gap-4 md:grid-cols-2">
                                <div className="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <h3 className="mb-2 font-semibold text-gray-900 dark:text-white">🎯 Nuestra Misión</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Promover la participación ciudadana activa y el control social efectivo 
                                        para garantizar transparencia y desarrollo territorial.
                                    </p>
                                </div>
                                <div className="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <h3 className="mb-2 font-semibold text-gray-900 dark:text-white">🚀 Funcionalidades</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Sistema integral con IA, mapas interactivos, auditoría forense 
                                        y herramientas de participación ciudadana.
                                    </p>
                                </div>
                            </div>

                            <div className="mb-6">
                                <h3 className="mb-3 font-semibold text-gray-900 dark:text-white">✨ Características Principales</h3>
                                <ul className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                    <li className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-blue-500"></span>
                                        Sistema de PQRSFD (Peticiones, Quejas, Reclamos, Sugerencias, Felicitaciones y Denuncias)
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-blue-500"></span>
                                        Mecanismos de participación ciudadana y acciones constitucionales
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-blue-500"></span>
                                        Inteligencia artificial para análisis y recomendaciones
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-blue-500"></span>
                                        Dashboard geográfico interactivo para seguimiento territorial
                                    </li>
                                </ul>
                            </div>

                            {!auth.user && (
                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        href={login()}
                                        className="inline-block rounded-lg bg-blue-600 px-6 py-3 text-center text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800"
                                    >
                                        Acceder al Sistema
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="inline-block rounded-lg border border-blue-600 bg-transparent px-6 py-3 text-center text-sm font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20"
                                    >
                                        Registrarse
                                    </Link>
                                </div>
                            )}
                        </div>
                        <div className="relative -mb-px aspect-[335/376] w-full shrink-0 overflow-hidden rounded-t-lg bg-gradient-to-br from-blue-600 to-indigo-700 lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:from-blue-800 dark:to-indigo-900">
                            <div className="flex h-full items-center justify-center p-8">
                                <div className="text-center text-white">
                                    <div className="mb-6 flex justify-center">
                                        <div className="flex h-24 w-24 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                            <svg className="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                    </div>
                                    <h3 className="mb-4 text-2xl font-bold">Desarrollo Territorial</h3>
                                    <p className="text-blue-100">
                                        Plataforma integral para la veeduría ciudadana y el desarrollo sostenible 
                                        de nuestros territorios.
                                    </p>
                                </div>
                            </div>
                            
                            {/* Elementos decorativos */}
                            <div className="absolute top-4 right-4">
                                <div className="h-2 w-2 rounded-full bg-white/30"></div>
                            </div>
                            <div className="absolute bottom-6 left-6">
                                <div className="h-1 w-1 rounded-full bg-white/40"></div>
                            </div>
                            <div className="absolute top-1/2 left-4">
                                <div className="h-1.5 w-1.5 rounded-full bg-white/20"></div>
                            </div>
                        </div>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}