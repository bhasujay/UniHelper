import re

def extract_classes(css_path):
    """Extracts all class selectors from a CSS file and returns a set of class names."""
    with open(css_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Matches patterns like ".class-name", ".btn-primary:hover", etc.
    pattern = r'\.([a-zA-Z0-9_-]+)'
    classes = re.findall(pattern, content)
    
    return set(classes)

def main():
    path1 = input("Enter the path of the first CSS file: ").strip()
    path2 = input("Enter the path of the second CSS file: ").strip()
    
    classes1 = extract_classes(path1)
    classes2 = extract_classes(path2)
    
    intersection = classes1.intersection(classes2)
    
    print("\nCommon classes found in both files:")
    if intersection:
        for cls in sorted(intersection):
            print(cls)
    else:
        print("No common classes found.")

if __name__ == "__main__":
    main()
