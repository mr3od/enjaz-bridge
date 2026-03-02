// Components
import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <AuthLayout
            title="Verify phone"
            description="Please verify your phone by clicking on the verification link we sent."
        >
            <Head title="Phone verification" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent for your phone
                    verification.
                </div>
            )}

            <Form action="/phone/verify" method="post" className="space-y-4">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2 text-left">
                            <Label htmlFor="code">OTP code</Label>
                            <Input
                                id="code"
                                name="code"
                                type="text"
                                autoComplete="one-time-code"
                                placeholder="123456"
                                maxLength={6}
                                dir="ltr"
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Verify phone
                        </Button>
                    </>
                )}
            </Form>

            <Form {...send.form()} className="mt-4 space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="outline">
                            {processing && <Spinner />}
                            Resend verification OTP
                        </Button>
                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
