import os
import sys
import time
import requests
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

# Target URL & Cookies from user's active session
url = "https://lpjk.pu.go.id/laporan-lpjk/searching"

cookies = {
    "csrf_cookie_name": "3c20131834a1f6b05791c999c21f1fbf",
    "ci_session": "qu3qh8993gd15tce1nlm64ptoq65q618",
    "XSRF-TOKEN": "eyJpdiI6InlwMmxraVNzTytjZWF4RmlUZzZHbnc9PSIsInZhbHVlIjoidUlSN2x2VnlHcmpMaUJSSVJFdTh5VXNCMURDUDZsSTBBbHVYaFZ6Mnpqc2ZyLzFuL1FxY0FjMldVWnhucXhEQTlwQ3lvdDc4TmltV3ZrZEN4T1F6aGhRbTJ0SXJONDRKM2ZGc3d6UVlRbDdZeW84RFVOVFVHaFVvVDhGQzdlT0siLCJtYWMiOiI4ZTM2MzkyYzRjZjcxYTMxOWIxYmM2OWNhNTczYzA1ODQ2MzVlM2FjMmFiMGRmY2Y0N2ZhM2E5MGZhMTI3ODdlIn0=",
    "laravel_session": "eyJpdiI6Ii9OTG44ajN1bTJCZ28rcllvQ0dKclE9PSIsInZhbHVlIjoiRzF2dzdscHdOUU01aFc5eTUrR2NLZG15N0t5VmdzYzE5dVV0L2lmbVZkZkJOb0pUMU1aMEF1ZXNvZmJaMU9XaitKYm5KVGRTN2Y5UjcrWjVCK1dVTVdnQ1ZGbWsxMDdHVEFWK1dWVUF3bS9icXhyMExrelczQjNzbWlpSm1YUm4iLCJtYWMiOiIyNWNjNzQ5MDNjYTZlNmJjYTE0NDkwNDAxZmJmNmMxOGYwODI3YmE2ZWU3YTViNmZhNjRmMjZlYTg0YWRkZWQ1In0="
}

headers = {
    "authority": "lpjk.pu.go.id",
    "accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
    "accept-language": "id,en-GB;q=0.9,en;q=0.8,en-US;q=0.7",
    "cache-control": "max-age=0",
    "origin": "https://lpjk.pu.go.id",
    "referer": "https://lpjk.pu.go.id/laporan-lpjk/sebaran/cari",
    "sec-ch-ua": '"Chromium";v="152", "Not?A_Brand";v="24", "Google Chrome";v="152"',
    "sec-ch-ua-mobile": "?0",
    "sec-ch-ua-platform": '"Windows"',
    "sec-fetch-dest": "document",
    "sec-fetch-mode": "navigate",
    "sec-fetch-site": "same-origin",
    "sec-fetch-user": "?1",
    "upgrade-insecure-requests": "1",
    "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36",
    "Content-Type": "application/x-www-form-urlencoded"
}

body_str = "_token=RbqQdRWEHVp6ESPjHruqdKdyxYk2ZFTrYx8Ys0ua&data=&propinsi=&kabupaten=&kualifikasi=&g-recaptcha-response=0cAFcWeA5D66MJwpKgunAhVHk8IKXYQoTp9Rd3J7A0dW7JXPLZN_FPLNGXPqndDZzi_wdwQDeuiX5lZeehtnEZcxXpwBgY1qjANv4d-ikC1SCjIg3l31uKkuo6TOYL61YG1-9_j3gpDVJXQImKebggYWLt_56CM0gy_YmGgsY5kXkgLD2ptHQkc6Lf-WQW9CZFA39E-Vk725ZI5TBGXs0oqI9eZdW3IUXhHHzDf8Jx2DKyHhT9xYhdd3farQGA8Nduo5-97ewO_cpUTHyISkxnAdVYrcElqWcKIXWuABPXgtSuCJ1DImm8MB1sXXASeWVcQ9WAjup7UIi9OH6ibH1gc98iuHHhoWqO9rDU30WdmC_4Jsw-tRsqrfWBJPVq47jhI9C50pw5MrQI66LCp1QVON1eKqkzLxeZoPuyeEzvgeMMWEqn4YybM9VrVl8u_vPPAMi6AN4LP8tcAwDFAzYxGTvCFqHe9t87vFRuraGbtJs0ZWqu2J_lb7yRgdw7-4vuxSnvLWEFUbUUizRRz3e59fIsUc3eVvWAbhfAsfZfMpz0VS8Yb2MolYbVYDwdnWFP_akXg3WVafcworjmDXjWzTjrIH_jojBy1CKAsdT6KDkeXDYTNGx--xkulhHjuLAjmIDKpinIPpypUcZuwJCH2I6Ospgx1EjFKPVeK0iecA1_tE1OvfDeJeu4SBuUZy94x14g39mHcaVc99yYlPJ7O6TG0Y1VVKj2f08tY9R6PDtc4camTCXuHx-GdAsqsJOAVwK6f8Ah2jRS5FG4Rw9snoilACjC7Mk6ZS7V9AXVkM-ZCP4N47bMYzIkpN4yIGN7KGo1AX4Kyfu6YN4RZigRZcAq0_CVAW9YFma1ibcNCSIOy8bJkSbkZBXLSdReQ1l6CdpsmcpSE0eJEOXZdqdnfCaLubW2rYvPau9oktgFNIrangmmmebwzT32dHy5evnVEKDTzEVRd521TtJPYNCwmQv8leORt_Xss9K_WPt3ThUijCM8BaBnCEBeodJpYErb4yU5j-kWVNwI9-ChqBfHJ2p0KtrQwMLiYlna177sih5el3Zsk4tSTDv4xnMcZMEVCA55bwBze4UybpZNRyhms1LlBvddkWzAxrod7l38L8mD4C1ixeMZUjmlD-YKldxRJBX49LCIOzg5mY7sIPPFZhdxo-lp116YUUSG3pwnurXUT0i8QoOKZCxaSsrZYDowX78E5nuQDh0400CFOJY3FSewRGIx5Fomrixgb9HHdaWJ4iok5gFu8vTGV53JUovQbEVA4-L7MSDASn4TqAmCnJmGQsQBZQbWff3bbk5zWUKuBBNSbAjxY764LYEGz7eVbHIYIs43lVVfQdlqVfUTQ4cb0dKKBGJoKIoUIZeVENQ3jP6D3EMskf9JhXn2fd7nWneSTHHdzFmvsonCcdwNnSS3uUW1d2BbBdAj9wcRGOA2WG14irfUxoXeqkDvGIXs86oRRHjiG1_3_HTo74Yu9p5jo8rKn1ot2iTPXZE4WYv780ivJXCoyTWKt4kB0j2Kl_9mb0334l6VxxTmRJUL9JGX0nEjz_lRc1DZ6r_VQqwaeskvv6Z-CCdGm2SoFQU6FxoRIOy7wkx5ipVvqM4jdHbNzLBdyKdQkNcSK8tGiWjPy0clhWy2FLO2xQn1vIdvEndBEzhZPCNd5-HTJmiGQpxNStEGcA2wYdkqAVTbI1k4s3vAtG3G421Bw4RpjJzeIB3nG_kNRsRKFmrYw55_Lx5gZYr-9xhxr91SZxNS_QkgOw_sdHb3RgVZ9zLh0ILhd7DP4Hlmgx9h71mK3I9dJIfFlxJpbQcf4Pu1A7_JPbapIpIppRLh1Qqnp_IFXlmPMiP8S52TAxCFhHm6cgpKGHbBEp6axkgJyh3hEgsNgeFIgH8FeJHF6A2LPtMvdBqtlNMmNiruT_VrcLqLi8DkDPW17vTzZDzJ_PSFLoi5ZP3YI2BJWXfIqIxfMRtDh01RFWQ8edcOfgoghb3GS3PwyBYS7gXJEEZlSnAbWDT5OW-VlUAjBuc3DlTGfmTn4tPmo7Q4JiDSXzFLhZ391UfKn0Kpm9mKTf9claEBWVIOy9FKWh-geKdQ0aVwGSbB1yN20Mr3ybSLbqtjLnjc6WCnXIAQ2vrSykoWzJtQhlnskxy78rxf4S11oGKzKiKBV9N_O5gVYXixwTs6BRB6FCQB3aLsH_4NeTS31PmZNv5ydbWY3xhgjFDWKcBmyvhKYYZXQsaS5mm167i7YXq3A8Vy-ffH7gpxoFUrIHLf0zxZtQ03CvhIm8HXogRPMM5x_N0ozkRizCyyp2FRtynUBwtBQZc5qHTiShucp5Wq6F_dhMtdR-g-RmJAY4tCb9W_4UDYhC1D-YJvqp92iJM5vS2xQ16Vo0YQ_RGuhw"

print("[*] Mengirim POST request dengan session & token reCAPTCHA ke LPJK...")
start = time.time()
try:
    resp = requests.post(url, headers=headers, cookies=cookies, data=body_str, verify=False, timeout=300)
    print(f"[+] Response status: {resp.status_code}")
    print(f"[+] Download size: {len(resp.content) / (1024*1024):.2f} MB ({time.time() - start:.2f} s)")
    
    out_file = "c:/laragon/www/infoajip/searching.html"
    with open(out_file, "wb") as f:
        f.write(resp.content)
    print(f"[+] File HTML berhasil disimpan ke: {out_file}")
except Exception as e:
    print(f"[!] Error: {e}")
