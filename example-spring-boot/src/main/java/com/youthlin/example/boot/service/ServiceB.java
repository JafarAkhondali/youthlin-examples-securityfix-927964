package com.youthlin.example.boot.service;

import org.springframework.core.annotation.Order;
import org.springframework.stereotype.Service;

@Service
@Order(10)
public class ServiceB implements IMyService {
    @Override
    public void doSomeThing() {

    }
}
