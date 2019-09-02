package com.youthlin.example.compiler.linscript.semantic;

import com.google.common.collect.Maps;
import lombok.AccessLevel;
import lombok.AllArgsConstructor;
import lombok.Data;

import java.util.Map;

/**
 * 数组类型
 *
 * @author : youthlin.chen @ 2019-08-31 23:11
 */
@Data
@AllArgsConstructor(access = AccessLevel.PRIVATE)
public class ArrayType implements IType {
    private IType elementType;
    private static final Map<IType, ArrayType> cache = Maps.newHashMap();

    public static ArrayType buildFromElementType(IType elementType) {
        return cache.computeIfAbsent(elementType, ArrayType::new);
    }

    @Override
    public String getTypeName() {
        return elementType.getTypeName() + "[]";
    }

}
