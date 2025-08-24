import re

def extract_classes_from_css(css_content):
    """Extract all class names from CSS content."""
    return set(re.findall(r"\.([a-zA-Z0-9_-]+)", css_content))

def extract_classes_from_html(html_content):
    """Extract all class names from HTML content."""
    class_attributes = re.findall(r'class="([^"]+)"', html_content)
    classes = set()
    for attr in class_attributes:
        for c in attr.split():
            classes.add(c.strip())
    return classes

def main():
    # Step 1: Ask for CSS files
    css_files = input("Enter CSS file paths separated by commas: ").split(",")
    css_classes = set()

    for css_file in css_files:
        css_file = css_file.strip()
        try:
            with open(css_file, "r", encoding="utf-8") as f:
                css_classes.update(extract_classes_from_css(f.read()))
        except FileNotFoundError:
            print(f"⚠️ File not found: {css_file}")

    # Step 2: Ask for HTML file
    html_file = input("Enter HTML file path: ").strip()
    try:
        with open(html_file, "r", encoding="utf-8") as f:
            html_classes = extract_classes_from_html(f.read())
    except FileNotFoundError:
        print(f"⚠️ File not found: {html_file}")
        return

    # Step 3: Find unused classes
    unused_classes = html_classes - css_classes

    # Output result
    if unused_classes:
        print("\nClasses in HTML but not found in CSS:")
        for cls in sorted(unused_classes):
            print("-", cls)
    else:
        print("\n✅ All HTML classes are defined in the provided CSS files.")

if __name__ == "__main__":
    main()
