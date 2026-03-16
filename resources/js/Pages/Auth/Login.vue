<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// Toastr Configuration
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
};

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post('/login', {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
        onError: () => {
            toastr.error('Invalid email or password. Please try again.');
        },
    });
};
</script>

<template>
    <Head title="MMSU Login" />    

    <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4 font-garamond">
        <div class="bg-white p-8 lg:p-12 rounded-2xl shadow-2xl w-full max-w-md border border-gray-100 relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-2 bg-[#0C4B05]"></div>

            <div class="flex justify-center mb-4">
                <img src="/mmsulogo.png" alt="MMSU Logo" class="w-62 h-32 object-contain" />
            </div>

            <div class="text-center mb-10">
                <h1 class="text-[#0C4B05] font-black text-xl uppercase tracking-wider leading-tight">
                    Mariano Marcos<br>State University
                </h1>
                <p class="text-gray-500 text-sm mt-2">Stakeholder Feedback System</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="relative">
                    <input
                        v-model="form.email"
                        type="email"
                        id="email"
                        required
                        placeholder=" " 
                        class="peer w-full px-4 py-3.5 rounded-xl border bg-transparent focus:border-[#0C4B05] focus:ring-1 focus:ring-[#0C4B05] outline-none text-gray-700 transition-all placeholder-transparent"
                        :class="form.errors.email ? 'border-red-500' : 'border-gray-300'"
                    />
                    <label 
                        for="email"
                        class="absolute left-4 px-1 bg-white text-gray-400 text-sm transition-all pointer-events-none
                               top-3.5 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base
                               peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[#0C4B05]
                               peer-[:not(:placeholder-shown)]:-top-2.5 peer-[:not(:placeholder-shown)]:text-xs"
                    >
                        Email Address
                    </label>
                    <div v-if="form.errors.email" class="text-red-600 text-xs mt-1 ml-1 font-medium italic">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div class="relative">
                    <input
                        v-model="form.password"
                        type="password"
                        id="password"
                        required
                        placeholder=" "
                        class="peer w-full px-4 py-3.5 rounded-xl border bg-transparent focus:border-[#0C4B05] focus:ring-1 focus:ring-[#0C4B05] outline-none text-gray-700 transition-all placeholder-transparent"
                        :class="form.errors.password ? 'border-red-500' : 'border-gray-300'"
                    />
                    <label 
                        for="password"
                        class="absolute left-4 px-1 bg-white text-gray-400 text-sm transition-all pointer-events-none
                               top-3.5 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base
                               peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-[#0C4B05]
                               peer-[:not(:placeholder-shown)]:-top-2.5 peer-[:not(:placeholder-shown)]:text-xs"
                    >
                        Password
                    </label>
                    <div v-if="form.errors.password" class="text-red-600 text-xs mt-1 ml-1 font-medium italic">
                        {{ form.errors.password }}
                    </div>
                </div>

                <button
                    :disabled="form.processing"
                    class="w-full bg-[#0C4B05] hover:bg-[#083604] text-white font-bold py-4 rounded-xl transition-all duration-300 mt-4 shadow-lg shadow-green-900/20 disabled:opacity-70 flex items-center justify-center gap-2"
                >
                    <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ form.processing ? 'Signing In...' : 'Sign In' }}</span>
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="#" class="text-xs text-gray-400 hover:text-[#0C4B05] transition-colors">Forgot your password?</a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.font-garamond {
    font-family: "adobe-garamond-pro", "Garamond", "Georgia", serif;
}

/* Ensure background is visible on autofill */
input:-webkit-autofill {
    -webkit-box-shadow: 0 0 0px 1000px white inset !important;
}

input:-webkit-autofill + label {
    top: -0.6rem !important;
    font-size: 0.95rem !important;
    color: #0C4B05 !important;
}
</style>