import time
import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager

# --- CONFIGURATION ---
BASE_URL = "http://localhost/RenderShelf"

# Setup Chrome Options
chrome_options = Options()
# chrome_options.add_argument("--headless")
driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
driver.maximize_window()

def take_screenshot(name):
    if not os.path.exists('test_results'):
        os.makedirs('test_results')
    driver.save_screenshot(f"test_results/{name}.png")

try:
    print("\n--- Starting Case 2: Registration ---\n")
    
    # Use a dynamic email for registration to avoid "email already exists" errors
    REG_USER = "NewUser_" + str(int(time.time()))
    REG_EMAIL = "new_" + str(int(time.time())) + "@example.com"
    
    driver.get(f"{BASE_URL}/register.php")
    driver.find_element(By.NAME, "username").send_keys(REG_USER)
    driver.find_element(By.NAME, "email").send_keys(REG_EMAIL)
    driver.find_element(By.NAME, "password").send_keys("Pass12345")
    driver.find_element(By.NAME, "confirm_password").send_keys("Pass12345")
    take_screenshot("registration_form")
    driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    time.sleep(2)
    print(f"✓ Case 2 Successful: Account Registered ({REG_EMAIL}).")
    
    # Logout
    driver.get(f"{BASE_URL}/logout.php")
    time.sleep(1)

except Exception as e:
    print(f"X Test Failed: {str(e)}")
    take_screenshot("case2_error")

finally:
    driver.quit()
