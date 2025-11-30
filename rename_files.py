import os
import sys
import re

def rename_files_in_directory(directory):
    print(f"开始处理目录: {directory}")
    # 检查目录是否存在
    if not os.path.isdir(directory):
        print(f"错误：目录 '{directory}' 不存在")
        return
    
    # 获取目录中的所有文件（不包括子目录）
    try:
        files = [f for f in os.listdir(directory)
                 if os.path.isfile(os.path.join(directory, f))]
    except PermissionError:
        print(f"错误：没有权限访问目录 '{directory}'")
        return
    
    print(f"在目录中找到 {len(files)} 个文件")
    if not files:
        print(f"目录 '{directory}' 中没有文件")
        return
    
    # 分析已有的序号文件
    numbered_files = {}
    unnumbered_files = []
    
    # 正则表达式匹配以数字开头的文件名
    pattern = re.compile(r'^(\d+)(\..+)$')
    
    for filename in files:
        match = pattern.match(filename)
        if match:
            # 提取序号和扩展名
            number = int(match.group(1))
            ext = match.group(2)
            numbered_files[number] = (filename, ext)
        else:
            unnumbered_files.append(filename)
    
    print(f"找到 {len(numbered_files)} 个已编号文件，{len(unnumbered_files)} 个未编号文件")
    
    # 找出最大序号
    max_number = max(numbered_files.keys()) if numbered_files else 0
    
    # 找出缺失的序号
    missing_numbers = [i for i in range(1, max_number + 1) if i not in numbered_files]
    
    # 按文件名排序未编号的文件
    unnumbered_files.sort()
    
    # 先处理缺失的序号
    to_rename = []
    
    for number in missing_numbers:
        if unnumbered_files:
            filename = unnumbered_files.pop(0)
            _, ext = os.path.splitext(filename)
            to_rename.append((filename, f"{number}{ext}"))
    
    # 再处理剩余的未编号文件
    start_number = max_number + 1
    for filename in unnumbered_files:
        _, ext = os.path.splitext(filename)
        to_rename.append((filename, f"{start_number}{ext}"))
        start_number += 1
    
    # 执行重命名
    print("\n开始重命名文件:")
    for old_name, new_name in to_rename:
        old_path = os.path.join(directory, old_name)
        new_path = os.path.join(directory, new_name)
        
        print(f"正在处理: {old_name} -> {new_name}")
        
        if os.path.exists(new_path):
            print(f"警告：文件 '{new_name}' 已存在，跳过重命名 '{old_name}'")
            continue
            
        try:
            os.rename(old_path, new_path)
            print(f"成功重命名: '{old_name}' -> '{new_name}'")
        except Exception as e:
            print(f"重命名 '{old_name}' 失败: {e}")

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("用法: python script.py <目录>")
        print("示例: python script.py ./my_folder")
        sys.exit(1)
    
    target_directory = sys.argv[1]
    print(f"目标目录: {target_directory}")
    print(f"绝对路径: {os.path.abspath(target_directory)}")
    
    rename_files_in_directory(target_directory)
    print("处理完成")