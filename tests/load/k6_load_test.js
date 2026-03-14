import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    vus: 50,
    duration: '30s',
    thresholds: {
        http_req_duration: ['p(95)<1000'], // 95% of requests under 1s
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost';

export default function () {
    // ---------------------------------------------------------------
    //  Health Check (no auth required)
    // ---------------------------------------------------------------
    let healthRes = http.get(`${BASE_URL}/api/v1/health`);
    check(healthRes, {
        'health status 200': (r) => r.status === 200,
        'health body has status ok': (r) => {
            let body = JSON.parse(r.body);
            return body.status === 'ok';
        },
    });

    // ---------------------------------------------------------------
    //  Authenticated Requests
    // ---------------------------------------------------------------

    // Obtain a JWT token
    let authPayload = JSON.stringify({
        email: __ENV.TEST_EMAIL || 'admin@rooibok.co.ug',
        password: __ENV.TEST_PASSWORD || 'Admin1234!',
    });

    let authRes = http.post(`${BASE_URL}/api/v1/auth/token`, authPayload, {
        headers: { 'Content-Type': 'application/json' },
    });

    let token = '';
    if (authRes.status === 200) {
        let authBody = JSON.parse(authRes.body);
        token = authBody.token || '';
    }

    if (token) {
        let authHeaders = {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
            },
        };

        // Attendance status check
        let statusRes = http.get(
            `${BASE_URL}/api/v1/attendance/status?employee_id=1`,
            authHeaders
        );
        check(statusRes, {
            'attendance status 200 or 404': (r) => r.status === 200 || r.status === 404,
        });

        // Subscription status
        let subRes = http.get(
            `${BASE_URL}/api/v1/subscription/status`,
            authHeaders
        );
        check(subRes, {
            'subscription status reachable': (r) => r.status < 500,
        });
    }

    sleep(1);
}
